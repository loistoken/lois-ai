<?php

class MeowPro_MWAI_Forms {
  private $core = null;
  private $namespace = 'ai-forms/v1';

  function __construct() {
    global $mwai_core;
    $this->core = $mwai_core;
    if ( is_admin() ) { return; }

    add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
    add_shortcode( 'mwai-form-field', array( $this, 'shortcode_mwai_form_field' ) );
    add_shortcode( 'mwai-form-submit', array( $this, 'shortcode_mwai_form_submit' ) );
    add_shortcode( 'mwai-form-output', array( $this, 'shortcode_mwai_form_output' ) );
    add_shortcode( 'mwai-form-container', array( $this, 'shortcode_mwai_form_container' ) );
    if ( $this->core->get_option( 'shortcode_chat_styles' ) ) {
      add_filter( 'mwai_forms_style', [ $this, 'apply_forms_styles' ], 10, 2 );
    }
  }

  function rest_api_init() {
		try {
			register_rest_route( $this->namespace, '/submit', array(
				'methods' => 'POST',
				'callback' => array( $this, 'rest_submit' ),
        'permission_callback' => '__return_true'
			) );
		}
		catch ( Exception $e ) {
			var_dump( $e );
		}
	}

  function rest_submit( $request ) {
    try {
			$params = $request->get_json_params();
      $prompt = isset( $params['prompt'] ) ? $params['prompt'] : "";
      $model = isset( $params['model'] ) ? $params['model'] : "gpt-3.5-turbo";

      if ( $model === 'dall-e' ) {
        $query = new Meow_MWAI_QueryImage( $prompt );
      }
      else {
        $query = new Meow_MWAI_QueryText( $prompt, 4096 );
      }
      $query->injectParams( $params );
			$answer = $this->core->ai->run( $query );
      $rawText = $answer->result;

      $html = '';
      if ( $model === 'dall-e' ) {
        foreach ( $answer->results as $result ) {
          $html .= '<img src="' . $result . '" />';
        }
      }
      else {
        $html = apply_filters( 'mwai_form_answer', $rawText  );
        $html = $this->core->markdown_to_html( $rawText );
      }
			return new WP_REST_Response([ 'success' => true, 'answer' => $rawText,
        'html' => $html, 'usage' => $answer->usage ], 200 );
		}
		catch ( Exception $e ) {
			return new WP_REST_Response([ 'success' => false, 'message' => $e->getMessage() ], 500 );
		}
  }

  // Based on the id, label, type, name and options, it will return the HTML code for the field.
  function shortcode_mwai_form_field( $atts ) {
    $id = isset( $atts['id'] ) ? $atts['id'] : null;
    $label = isset( $atts['label'] ) ? $atts['label'] : null;
    $type = isset( $atts['type'] ) ? $atts['type'] : null;
    $name = isset( $atts['name'] ) ? $atts['name'] : null;
    $options = isset( $atts['options'] ) ? $atts['options'] : null;
    $options = urldecode( $options );
    $options = json_decode( $options );
    $required = isset( $atts['required'] ) ? $atts['required'] : 'yes';
    $placeholder = isset( $atts['placeholder'] ) ? $atts['placeholder'] : null;
    $default = isset( $atts['default'] ) ? $atts['default'] : null;
    $maxlength = isset( $atts['maxlength'] ) ? $atts['maxlength'] : null;
    $rows = isset( $atts['rows'] ) ? $atts['rows'] : null;
    //$value = isset( $atts['value'] ) ? $atts['value'] : null;
    $baseClass = 'mwai-form-field mwai-form-field-' . $type;
    $class = $baseClass . ( isset( $atts['class'] ) ? ' ' . $atts['class'] : '' );
    $html = '';
    $html .= '<fieldset class="' . $class . '">';
    switch ( $type ) {
      case 'select':
        if ( !empty( $options ) ) {
          $html .= '<legend>' . $label . '</legend>';
          $html .= '<div class="mai-form-field-container">';
          $html .= '<select name="' . $name . '" ' . ( $required == 'yes' ? 'required' : '' ) . ' >';
          foreach ( $options as $option ) {
              $html .= '<option value="' . $option->value . '">' . $option->label . '</option>';
          }
          $html .= '</select>';
          $html .= '</div>';
        }
        break;
      case 'radio':
        if ( !empty( $options ) ) {
          $html .= '<legend>' . $label . '</legend>';
          foreach ( $options as $option ) {
            $html .= '<div class="mai-form-field-container">';
            $html .= '<input type="radio" name="' . $name . '" value="' . $option->value . '" ' .
              ( $required == 'yes' ? 'required' : '' ) . ' />';
            $html .= '<label>' . $option->label . '</label>';
            $html .= '</div>';
          }
        }
        break;
      case 'checkbox':
        $html .= '<legend>' . $label . '</legend>';
        foreach ( $options as $option ) {
          $html .= '<div class="mai-form-field-container">';
          $html .= '<input type="checkbox" name="' . $name . '" value="' . $option->value . '" ' . ( $required == 'yes' ? 'required' : '' ) . ' />';
          $html .= '<label>' . $option->label . '</label>';
          $html .= '</div>';
        }
        break;
      case 'textarea':
        $html .= '<legend>' . $label . '</legend>';
        $html .= '<div class="mai-form-field-container">';
        $html .= '<textarea id="' . $id . '" name="' . $name . '"';
        $html .= 'placeholder="' . $placeholder . '" ';
        $html .= 'maxlength="' . $maxlength . '" ';
        $html .= 'rows="' . $rows . '" ';
        $html .= ( $required == 'yes' ? 'required' : '' ) . ' />';
        $html .= $default;
        $html .= '</textarea>';
        $html .= '</div>';
        break;
      default:
        $html .= '<legend>' . $label . '</legend>';
        $html .= '<div class="mai-form-field-container">';
        $html .= '<input id="' . $id . '" type="text" name="' . $name . '"';
        $html .= 'placeholder="' . $placeholder . '" ';
        $html .= 'maxlength="' . $maxlength . '" ';
        $html .= 'value="' . $default . '" ';
        $html .= ( $required == 'yes' ? 'required' : '' ) . ' />';
        $html .= '</div>';
        break;
    }
    $html .= '</fieldset>';
    return $html;
  }

  function write_submit_js_function( $id, $atts ) {
    
    // Forms System Parameters
    $atts = apply_filters( 'mwai_forms_params', $atts );
    $env = empty( $atts['env'] ) ? 'form' : $atts['env'];
    $sessionId = $this->core->get_session_id();
    $rest_nonce = wp_create_nonce( 'wp_rest' );
    $idForFn = preg_replace( '/[^a-zA-Z0-9]/', '_', $id ); // New ID safe to be used as function names

    // OpenAI Parameters
    $model = !empty( $atts['model'] ) ? $atts['model'] : 'gpt-3.5-turbo';
    $prompt = !empty( $atts['prompt'] ) ? $atts['prompt'] : "";
    $prompt = addslashes( urldecode( $prompt ) );
    $prompt = preg_replace( '/\v+/', "\\n", $prompt );
    $outputElement = !empty( $atts['output_element'] ) ? $atts['output_element'] : "";
    $temperature = !empty( $atts['temperature'] ) ? $atts['temperature'] : 0.8;
    $maxTokens = !empty( $atts['max_tokens'] ) ? $atts['max_tokens'] : 2048;
    $apiKey = !empty( $atts['api_key'] ) ? $atts['api_key'] : "";

    // Named functions
    $onSubmitClickFn = "mwai_{$idForFn}_onSubmitClickFn";
    $initFormFn = "mwai_{$idForFn}_initFormFn";
    $promptBuilderFn = "mwai_{$idForFn}_promptBuilderFn";
    $setFormDisabledFn = "mwai_{$idForFn}_setFormDisabledFn";

    // Variables
    $apiUrl = get_rest_url( null, 'ai-forms/v1/submit' );

    ob_start();
    ?>
    <script>
      (function () {
        let submitButton = document.querySelector('#<?= $id ?>');

        function <?= $promptBuilderFn ?>() {
          let prompt = '<?= $prompt ?>';
          const matches = prompt.match(/{(.*?)}/g);
          const sysErrors = [];
          const userErrors = [];
          if (matches) {
            matches.forEach( function( match ) {
              const fieldName = match.replace(/{|}/g, '');

              // If the fieldName has # in it, it's a selector directly, otherwise it's a name attribute
              const selector = fieldName.includes('#') ? fieldName : `.mwai-form-field [name="${fieldName}"]`;
              const field = document.querySelectorAll(selector);

              // If field is radio or checkbox, get the values of the checked ones, separated by a comma
              if ( field.length > 1 ) {
                let fieldValue = '';
                if ( field[0].type == 'radio' ) {
                  field.forEach( function( radio ) {
                    if ( radio.checked ) {
                      fieldValue = radio.value;
                    }
                  });
                }
                else if ( field[0].type == 'checkbox' ) {
                  field.forEach( function( checkbox ) {
                    if ( checkbox.checked ) {
                      fieldValue += checkbox.value + ',';
                    }
                  });
                  fieldValue = fieldValue.slice(0, -1);
                }
                else {
                  // Alert: doesn't handle field[0].type
                  console.warn(`[FORM] Unhandled Field Type for Multi-Choices: '${field[0].type}'`);
                  sysErrors.push(`Unhandled Field Type for Multi-Choices: '${field[0].type}'`);
                }
                prompt = prompt.replace( match, fieldValue );
              }
              // If field is a single element, get its value
              else if ( field.length == 1 ) {
                let fieldValue = '';
                if ( field[0].tagName == 'SELECT' ) {
                  fieldValue = field[0].options[field[0].selectedIndex].value;
                }
                else if ( field[0].textContent ) {
                  fieldValue = field[0].textContent;
                }
                else if ( field[0].value ) {
                  fieldValue = field[0].value;
                }
                if (!fieldValue) {
                  console.warn(`[FORM] Field Empty: '${fieldName}'`);
                  userErrors.push(`Field Empty: '${fieldName}'`);
                }
                prompt = prompt.replace( match, fieldValue );
              }
              else {
                console.warn(`[FORM] Field Not Found: '${fieldName}'`);
                sysErrors.push(`Field Not Found: '${fieldName}'`);
              }
            });
          }
          if (sysErrors.length > 0) {
            alert("Some errors were found in this AI Form:\n\n" + sysErrors.join("\n") + 
              "\n\nPS: Make sure that your field names are uniques.");
            return null;
          }
          if (userErrors.length > 0) {
            alert("Some errors were found in this AI Form:\n\n" + userErrors.join("\n"));
            return null;
          }
          return prompt;
        }

        function <?= $setFormDisabledFn ?>( status ) {
          let formContainer = submitButton.closest('.mwai-form-container');
          if (formContainer) {
            formContainer.querySelectorAll('input, textarea, select, button').forEach( function( element ) {
              element.disabled = status;
            });
          }
          else {
            document.querySelectorAll('.mwai-form-submit button, .mwai-form-field input, .mwai-form-field textarea, .mwai-form-field select').forEach( function( element ) {
              element.disabled = status;
            });
          }
        }

        function <?= $onSubmitClickFn ?>() {
          var outputElement = document.querySelector('<?= $outputElement ?>');
          if (!outputElement) {
            alert("AI Engine: The Output Element could not be found.");
            console.warn("The Output Element could be found", '<?= $outputElement ?>');
            return;
          }
          var finalPrompt = <?= $promptBuilderFn ?>(outputElement);
          if (!finalPrompt) {
            return;
          }
          const data = {
            env: '<?= $env ?>',
            session: '<?= $sessionId ?>',
            prompt: finalPrompt,
            model: '<?= $model ?>',
            temperature: '<?= $temperature ?>',
            maxTokens: '<?= $maxTokens ?>',
            apiKey: '<?= $apiKey ?>',
          };
          console.log('[FORM] Sent: ', data);
          <?= $setFormDisabledFn ?>( true );
          fetch('<?= $apiUrl ?>', { method: 'POST', headers: { 
              'Content-Type': 'application/json', 
              'X-WP-Nonce': '<?= $rest_nonce ?>'
            },
            body: JSON.stringify(data)
          })
          .then(response => response.json())
          .then(data => {
            console.log('[FORM] Recv: ', data);
            if (data.success) {
              if (outputElement.tagName === 'TEXTAREA' || outputElement.tagName === 'INPUT') {
                outputElement.value = data.answer;
              }
              else {
                outputElement.innerHTML = data.html;
              }
            }
            else {
              alert("AI Engine: " + data.message);
            }
            <?= $setFormDisabledFn ?>( false );
          })
          .catch(error => {
            console.error(error);
            <?= $setFormDisabledFn ?>( false );
          });
        }

        function <?= $initFormFn ?>() {
          submitButton.addEventListener('click', <?= $onSubmitClickFn ?>);
        }

        window.addEventListener('load', <?= $initFormFn ?>);

      })();
    </script>
    <?php
    $html = ob_get_contents();
    ob_end_clean();
    return $html;
  }

  function shortcode_mwai_form_submit( $atts ) {
    $id = 'mwai-' . uniqid();
    $label = $atts['label'];
    $class = empty( $atts['class'] ) ? 'mwai-form-submit' : 'mwai-form-submit ' . $atts['class'];
    $html = '';
    $html .= '<div class="mwai-form-submit">';
    $html .= '<button id="' . $id . '" class="' . $class . '"><span>' . $label . '</span></button>';
    $html .= '</div>';
    $html .= $this->write_submit_js_function( $id, $atts );
    return $html;
  }

  function shortcode_mwai_form_output( $atts ) {
    $id = empty( $atts['id'] ) ? ( 'mwai-' . uniqid() ) : $atts['id'];
    $class = empty( $atts['class'] ) ? 'mwai-form-output' : 'mwai-form-output ' . $atts['class'];
    $html = '<div class="mwai-form-output" id="' . $id . '" class="' . $class . '">';
    $html .= '</div>';
    return $html;
  }

  function chatgpt_style( $id ) {
    $css = file_get_contents( MWAI_PATH . '/premium/forms-chatgpt.css' );
    $css = str_replace( '#mwai-form-id', "#mwai-form-container-{$id}", $css );
    return "<style>" . $css . "</style>";
  }

  function shortcode_mwai_form_container( $atts ) {
    $id = empty( $atts['id'] ) ? uniqid() : $atts['id'];
    $theme = strtolower( $atts['theme'] );
    $style_content = "";
    if ( $theme === 'chatgpt' ) {
      $style_content = $this->chatgpt_style( $id, $style_content );
    }
    $style_content = apply_filters( 'mwai_forms_style', $style_content, $id );
    return $style_content;
  }

  function apply_forms_styles( $css, $chatbotId ) {
    $chatStyles = $this->core->get_option( 'shortcode_chat_styles' );
    return preg_replace_callback( '/--mwai-(\w+):\s*([^;]+);/', function ( $matches ) use ($chatStyles ) {
      if( isset( $chatStyles[$matches[1]] ) ) {
        return "--mwai-" . $matches[1] . ": " . $chatStyles[$matches[1]] . ";";
      }
      return $matches[0];
    }, $css );
  }
}
