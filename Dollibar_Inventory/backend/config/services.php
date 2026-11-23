<?php
return [
 'mailgun'=>['domain'=>env('MAILGUN_DOMAIN'),'secret'=>env('MAILGUN_SECRET'),'endpoint'=>env('MAILGUN_ENDPOINT','api.mailgun.net'),'scheme'=>'https'],
 'postmark'=>['token'=>env('POSTMARK_TOKEN')],
 'ses'=>['key'=>env('AWS_ACCESS_KEY_ID'),'secret'=>env('AWS_SECRET_ACCESS_KEY'),'region'=>env('AWS_DEFAULT_REGION','us-east-1')],
 'crater'=>['url'=>env('CRATER_URL'),'token'=>env('CRATER_TOKEN'),'invoice_endpoint'=>env('CRATER_INVOICE_ENDPOINT','/api/v1/invoices')],
 'integration'=>['webhook_secret'=>env('INTEGRATION_WEBHOOK_SECRET')],
];
