<?php
$pricing_table = array(
    'lqdsep-pricing-table-base' => 'elements/pricing-table/pricing-table-base.css',
    'lqdsep-pricing-table-description' => 'elements/pricing-table/pricing-table-description.css',
    'lqdsep-pricing-table-description-md' => 'elements/pricing-table/pricing-table-description-md.css',
    'lqdsep-pricing-table-description-lg' => 'elements/pricing-table/pricing-table-description-lg.css',
    'lqdsep-pricing-table-featured' => 'elements/pricing-table/pricing-table-featured.css',
    'lqdsep-pricing-table-label' => 'elements/pricing-table/pricing-table-label.css',
    'lqdsep-pricing-table-scale-bg' => 'elements/pricing-table/pricing-table-scale-bg.css',
);
for ( $i = 1; $i <= 11; $i++ ) {
    $pricing_table['lqdsep-pricing-table-style-' . $i] = 'elements/pricing-table/pricing-table-style-' . $i . '.css';
    if ( $i === 3 ) {
        $pricing_table['lqdsep-pricing-table-style-' . $i . 'b'] = 'elements/pricing-table/pricing-table-style-' . $i . 'b.css';
    }
}