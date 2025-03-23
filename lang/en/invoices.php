<?php

return [
    'errors' => [
        'not_found' => 'Invoice not found.',
        'generic' => 'An error occurred while processing the invoice.',
        'invalid_status_transition_send' => 'Cannot send invoice: invoice must be in draft status.',
        'invalid_status_transition_mark_sent' => 'Cannot mark invoice as sent: invoice must be in sending status.',
        'invalid_status_transition' => 'Invalid status transition: :message',
        'no_product_lines' => 'Cannot send invoice: no product lines added.',
        'invalid_product_lines' => 'Cannot send invoice: one or more product lines are invalid.',
        'product_line' => 'Product line error: :message',
    ],

    'success' => [
        'created' => 'Invoice created successfully.',
        'product_line_added' => 'Product line added successfully.',
        'sent' => 'Invoice sent successfully.',
    ],

    'notifications' => [
        'subject' => 'Invoice #:id',
        'message' => 'Dear :customer, your invoice has been processed and is ready for review.',
    ],
];
