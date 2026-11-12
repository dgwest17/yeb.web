<?php
/**
 * /newsletter/sample.php?t=update|welcome|alert|tip — each email layout with sample text,
 * so the templates can be looked at before writing one (Admin → Newsletters shows the real
 * ones). Sample content only; nothing from the newsletters file or Zoho.
 */
require_once dirname(__DIR__) . '/newsletter-lib.php';
header('X-Robots-Tag: noindex');
$t = $_GET['t'] ?? 'update';
$samples = [
  'update' => ['template' => 'update', 'subject' => 'Lumaria Energy Update', 'date' => date('Y-m-d'),
    'intro' => '<p>We\'re here to keep you informed, inspired, and empowered. Each month we share solar tips, energy-saving ideas, company updates, answers to common questions, and opportunities for the Lumaria Energy community.</p>',
    'cards' => [
      ['title' => 'Energy tip of the month', 'text' => 'Run the dishwasher and washer/dryer during peak sun hours, and your own solar powers them instead of the grid.', 'image' => '/img/email/nl-tip.jpg', 'link_label' => 'More energy saving tips', 'link_url' => NL_BASE . '/knowledge/'],
      ['title' => 'Did you know?', 'text' => 'SDCP\'s battery rebate is $2,000–$6,000 per battery, depending on the project and the battery.', 'image' => '/img/email/nl-know.jpg', 'link_label' => 'Learn more', 'link_url' => NL_BASE . '/home-battery/'],
      ['title' => 'Customer corner', 'text' => '"The whole process was so easy and the team was amazing to work with!"', 'image' => '', 'link_label' => 'Read more stories', 'link_url' => NL_BASE . '/'],
    ], 'referral' => true, 'signoff' => "Talk soon,\nDavid West\n(858) 786-2204"],
  'welcome' => ['template' => 'welcome', 'subject' => 'Welcome to Lumaria Energy', 'greeting' => 'Hi there,',
    'body' => '<p>Welcome to lumaria energy! We\'re so excited to have you with us.</p><p>Our goal is to make your solar experience simple, transparent, and stress-free. From keeping you updated throughout the process to answering your questions, our team is here to support you every step of the way.</p><p>You\'ll also hear from us from time to time with helpful energy tips, important updates, and resources to help you get the most out of your system.</p>'],
  'alert' => ['template' => 'alert', 'subject' => 'Battery rebate update', 'headline' => 'SDCP battery rebate: funds are limited', 'deadline' => 'First come, first served while funding lasts',
    'body' => '<p>If you\'re in San Diego Community Power territory, adding a battery can come with $2,000–$6,000 back per battery.</p>', 'cta_label' => 'See if you qualify', 'cta_url' => NL_BASE . '/home-battery/', 'signoff' => "David West\n(858) 786-2204"],
  'tip' => ['template' => 'tip', 'subject' => 'One quick tip', 'headline' => 'Check your solar app once a month',
    'body' => '<p>A sunny day with low production is the first sign something needs a look.</p>', 'signoff' => "David West\n(858) 786-2204"],
];
echo nl_render($samples[$t] ?? $samples['update'], [], false);
