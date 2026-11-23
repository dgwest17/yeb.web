<?php
/**
 * landing-guides.php — guide pages for owners of orphaned systems and owners
 * staring at an inverter error. landing.php merges these into $PAGES, and
 * sitemap.php lists every key here, so a new guide needs: an entry below and
 * a folder with a three-line index.php (copy any guide's).
 *
 * Facts checked 2026-11-23 against the sources listed on each page. Bankruptcy
 * cases keep moving: re-check the servicer lines when a page's date gets old.
 * Write in our own words; never copy another company's page.
 */

// Shared pieces
$ORPHAN_FAQ = ['my-installer-went-out-of-business-am-i-screwed', 'what-should-i-watch-in-my-monitoring-app-and-when-should-i-worry',
               'battery-warranties-the-fine-print-that-actually-matters', 'how-much-maintenance-does-solar-actually-need'];
$ERROR_FAQ  = ['what-should-i-watch-in-my-monitoring-app-and-when-should-i-worry', 'what-is-a-solar-health-audit-and-do-i-need-one',
               'how-much-maintenance-does-solar-actually-need', 'i-got-a-surprise-true-up-bill-what-happened'];
$CLOSED = [
  ['SunPower', '/sunpower-out-of-business/', 'Chapter 11, August 2024. Leases and PPAs now with SunStrong Management.'],
  ['Sunnova', '/sunnova-bankruptcy/', 'Chapter 11, June 2025. Most accounts now serviced by SunStrong Management.'],
  ['Freedom Forever', '/freedom-forever-bankruptcy/', 'Chapter 11, April 2026; converted to Chapter 7 liquidation, August 2026.'],
  ['Titan Solar Power', '/titan-solar-out-of-business/', 'Stopped operating and filed Chapter 7, June 2024.'],
  ['Mosaic (solar loans)', '/mosaic-solar-loan-bankruptcy/', 'A lender, not an installer. Chapter 11, June 2025; loans now serviced by Solar Servicing.'],
  ['Sullivan Solar Power', '/sullivan-solar-closed/', 'San Diego installer that stopped operating in November 2021.'],
  ['PetersenDean', '/petersen-dean-bankruptcy/', 'Chapter 11, June 2020. Customer contracts went to SolarJuice American.'],
  ['Sungevity', '/sungevity-bankruptcy/', 'Chapter 11, March 2017. Some financing agreements went to Sunrun.'],
];
$ERRORS = [
  ['SolarEdge error codes', '/solaredge-error-codes/', 'Isolation faults, arc detection, and what the red light means.'],
  ['Enphase microinverter errors', '/enphase-microinverter-errors/', 'DC Resistance Low, GFI and microinverters not reporting.'],
  ['SMA Sunny Boy events', '/sma-inverter-errors/', 'Red LED, insulation events 3501–3701 and grid events.'],
  ['Tesla Powerwall alerts', '/tesla-powerwall-alerts/', 'Gateway offline, Breaker Open, Solar Production Limited.'],
  ['SolarCity / Tesla solar errors', '/solarcity-error-codes/', 'Older SolarCity systems: which inverter you have and what its lights and alerts mean.'],
];
$ORPHAN_BASE = [
  'price' => 'savings-audit',
  'cta'   => ['Get my system checked →', '/audit/'],
  'alt'   => ['Every closed installer we track', '/closed-solar-installers/'],
  'topic' => 'repair',
  'service' => 'Service for solar systems whose installer closed',
  'crumb' => ['Closed solar installers', '/closed-solar-installers/'],
  'faq'   => $ORPHAN_FAQ,
  'related_title' => 'Other closed installers',
  'related' => $CLOSED,
  'checked' => 'November 23, 2026',
];
$ERROR_BASE = [
  'price' => 'savings-audit',
  'cta'   => ['Have my system diagnosed →', '/audit/'],
  'alt'   => ['System not producing? Start here', '/solar-repair/'],
  'topic' => 'repair',
  'service' => 'Solar inverter and battery diagnostics',
  'crumb' => ['Solar error codes', '/solar-error-codes/'],
  'faq'   => $ERROR_FAQ,
  'related_title' => 'Other error guides',
  'related' => $ERRORS,
  'checked' => 'November 23, 2026',
];
$SAFETY = ['Safety first', [
  ['Smoke, sparks, a burning smell or a hot panel', 'Stay clear, and call 911 if anything is burning. Do not open the inverter, unplug connectors or climb on the roof.'],
  ['What you can safely do', 'Read the code, take a photo of the screen or app, and note when it started and the weather. Restart only the way the owner’s manual describes, and only once.'],
  ['When to stop', 'If the same fault comes back, it is telling you something is wrong. Repeated resets can hide a real wiring or insulation problem.'],
]];

$GUIDES = [
  // ─── Hubs ───────────────────────────────────────────────────────────────
  'closed-solar-installers' => [
    'title' => 'Closed Solar Installers List (2026) | Who Services Your System Now',
    'desc'  => 'Solar companies that went bankrupt or closed, including SunPower, Sunnova, Freedom Forever, Titan, Mosaic, Sullivan Solar, PetersenDean and Sungevity, and who services those systems now.',
    'kicker' => 'Closed solar installers',
    'h1'    => 'Your solar company closed. Here’s who to call now.',
    'lede'  => 'A closed installer doesn’t switch your panels off. It changes who covers each part: the equipment maker, the company that owns your lease or PPA, your lender, and you.',
    'answer' => 'When an installer goes bankrupt, the panels keep working. Manufacturer warranties on panels, inverters and batteries generally continue, but the installer’s own workmanship and roof warranties usually have no one behind them. Leases, PPAs and loans don’t end. They move to another company, which is the first call for those systems.',
    'points' => [
      ['Find your company below', 'Each page says what happened, who took over the accounts, and what to do first.'],
      ['Check who owns the system', 'Lease or PPA: the owner or servicer handles repairs. Cash or loan: you own it and hire the repair.'],
      ['Keep paying', 'A bankruptcy doesn’t cancel a loan, lease or PPA. Talk to the lender or servicer before changing payments.'],
    ],
    'related_title' => 'Closed installers and lenders',
    'related' => $CLOSED,
    'itemlist' => true,
    'sources' => [
      ['CSLB: Solar Smart', 'https://www.cslb.ca.gov/consumers/solar_smart/'],
      ['NerdWallet: your solar company went bankrupt', 'https://www.nerdwallet.com/home-ownership/solar/learn/solar-company-bankruptcy'],
    ],
    'checked' => 'November 23, 2026',
    'price' => 'savings-audit',
    'cta'   => ['Get my system checked →', '/audit/'],
    'alt'   => ['What to do when any installer closes', '/solar-installer-out-of-business/'],
    'topic' => 'repair',
    'service' => 'Service for solar systems whose installer closed',
    'faq'   => $ORPHAN_FAQ,
  ],
  'solar-error-codes' => [
    'title' => 'Solar Inverter Error Codes Explained | SolarEdge, Enphase, SMA, Tesla',
    'desc'  => 'What your solar inverter or battery error means, what is safe to try, and when to call a professional: SolarEdge, Enphase, SMA Sunny Boy and Tesla Powerwall.',
    'kicker' => 'Solar error codes',
    'h1'    => 'Red light on your inverter? Start here.',
    'lede'  => 'Most errors fall into three groups: the grid, the wiring on your roof, or the equipment itself. The code tells you which, and whether it’s safe to wait.',
    'answer' => 'A solar error usually means one of three things. The grid was out of range and the inverter stepped off for safety, which often clears on its own. The DC wiring or a panel is leaking to ground or arcing, which is a real fault that needs a qualified technician. Or a part failed or stopped communicating, which is often a warranty claim. Find your brand below for what each code means.',
    'points' => [
      ['Grid events', 'Voltage or frequency out of range. Often clears by itself; if it keeps happening, the utility side may need a look.'],
      ['Ground and arc faults', 'Insulation breaking down or a loose connection. Don’t keep resetting these.'],
      ['Communication', 'The system may be producing fine while the app shows nothing. Check the internet connection first.'],
    ],
    'sections' => [$SAFETY],
    'related_title' => 'Find your brand',
    'related' => $ERRORS,
    'itemlist' => true,
    'checked' => 'November 23, 2026',
    'price' => 'savings-audit',
    'cta'   => ['Have my system diagnosed →', '/audit/'],
    'alt'   => ['System not producing? Start here', '/solar-repair/'],
    'topic' => 'repair',
    'service' => 'Solar inverter and battery diagnostics',
    'faq'   => $ERROR_FAQ,
  ],

  // ─── Closed installers ──────────────────────────────────────────────────
  'sunpower-out-of-business' => ['_base' => 'orphan',
    'title' => 'Is SunPower Out of Business? What SunPower Owners Should Do (2026)',
    'desc'  => 'SunPower filed Chapter 11 in August 2024. Who services SunPower leases and PPAs now (SunStrong), what happened to the mySunPower app, and what cash and loan owners should do.',
    'kicker' => 'SunPower owners',
    'h1'    => 'SunPower went bankrupt. What happens to my system?',
    'lede'  => 'Your panels still work. What changed is who answers the phone, and that depends on whether you own the system.',
    'answer' => 'SunPower filed for Chapter 11 in August 2024 and stopped supporting customers through its app and phone line that September. SunStrong Management took over the SunPower leases and PPAs and the monitoring app, which is now called SunStrong Connect. If you bought your system with cash or a loan, you own it: SunStrong points owners to local licensed solar companies for service.',
    'points' => [
      ['Lease or PPA', 'SunStrong Management services it now. Call them first for repairs; it’s their equipment.'],
      ['Cash or loan', 'You own the system. Manufacturer warranties may cover parts, but usually not labor or shipping.'],
      ['Monitoring', 'The old app became SunStrong Connect. Enphase has sold a monitoring upgrade for some SunPower systems.'],
    ],
    'sections' => [
      ['What to do now', [
        ['Find out who owns it', 'Look at your agreement or latest statement. A lease or PPA means SunStrong or another owner; a loan or cash purchase means you.'],
        ['Check your monitoring', 'If the app shows the system “not communicating,” production may still be fine. Compare against your utility bill before paying for a visit.'],
        ['Collect serial numbers', 'Panels, inverters or microinverters, and any battery. Warranty claims start with the manufacturer and the serial number.'],
      ]],
      ['Adding a battery', [
        ['It can be done', 'Many SunPower systems can take a battery. The right one depends on which inverters you have.'],
        ['Protect your NEM status', 'A battery alone generally doesn’t end NEM 1.0 or 2.0; adding panels beyond the allowed amount can. Get it in writing.'],
      ]],
    ],
    'sources' => [
      ['EnergySage: SunPower and SunStrong', 'https://www.energysage.com/solar/sunpower-is-bankrupt-what-now'],
      ['SolarReviews: what SunPower customers need to know', 'https://solarreviews.com/blog/what-sunpower-customers-need-to-know-about-bankruptcy'],
      ['SunPower update page', 'https://us.sunpower.com/update'],
    ],
  ],
  'sunnova-bankruptcy' => ['_base' => 'orphan',
    'title' => 'Sunnova Bankruptcy: Who Services Your Solar Now? (2026)',
    'desc'  => 'Sunnova filed Chapter 11 in June 2025. Who services Sunnova leases, PPAs and loans now, whether you still have to pay, and what to do if your system stops producing.',
    'kicker' => 'Sunnova customers',
    'h1'    => 'Sunnova went bankrupt. Who do I call now?',
    'lede'  => 'Keep paying, keep your statements, and find the servicer’s name on your newest bill before calling anyone.',
    'answer' => 'Sunnova filed for Chapter 11 in June 2025 and sold its business in pieces. Most in-service Sunnova accounts are now serviced by SunStrong Management. An earlier plan to move servicing to Omnidian was replaced, so older letters may name the wrong company. Your agreement doesn’t end: keep paying as billed until the servicer tells you otherwise in writing.',
    'points' => [
      ['Read your latest statement', 'It names your current servicer and the number to call. That beats anything you find online.'],
      ['Keep paying', 'A lease, PPA or loan continues through a bankruptcy. Missed payments still count against you.'],
      ['Owned outright?', 'If you own the system and no servicer covers it, a local licensed solar company handles repairs.'],
    ],
    'sections' => [
      ['If your system stops producing', [
        ['Lease or PPA', 'Report it to the servicer first. The equipment belongs to the system owner, and repairs are usually theirs to arrange.'],
        ['Document everything', 'Dates, screenshots of the app, and your utility bills. A production drop shows up on the bill even when the app is down.'],
      ]],
    ],
    'sources' => [
      ['pv magazine: Sunnova files for bankruptcy', 'https://pv-magazine-usa.com/2025/06/10/sunnova-files-for-bankruptcy'],
      ['EnergySage: Sunnova bankruptcy', 'https://www.energysage.com/news/sunnova-bankruptcy/'],
    ],
  ],
  'freedom-forever-bankruptcy' => ['_base' => 'orphan',
    'title' => 'Freedom Forever Bankruptcy: What Customers Should Do (2026)',
    'desc'  => 'Freedom Forever filed Chapter 11 in April 2026 and converted to Chapter 7 liquidation in August 2026. What happens to your warranty, financing and unfinished install.',
    'kicker' => 'Freedom Forever customers',
    'h1'    => 'Freedom Forever is liquidating. What now?',
    'lede'  => 'Chapter 7 means the company is being wound down, not rebuilt. Plan as if its own warranties are gone.',
    'answer' => 'Freedom Forever filed for Chapter 11 in April 2026, and the case was converted to a Chapter 7 liquidation in August 2026. Its own workmanship and roof warranties are unlikely to be honored. Manufacturer warranties on panels, inverters and batteries generally continue. Your loan, lease or PPA is with the finance company, not the installer, so it continues and payments are still due.',
    'points' => [
      ['Finished and running', 'Your system works. Move monitoring into your own name and keep the manufacturer warranty papers.'],
      ['Not finished or not switched on', 'Call your finance company. The court allowed some finance partners to finish stranded installs.'],
      ['Claims', 'Deadlines in a bankruptcy are strict. Check the Chapter 7 trustee’s notices for what is still open.'],
    ],
    'sections' => [
      ['Systems never switched on', [
        ['Permission to Operate', 'A system needs final inspection and utility approval before it can run. Ask the finance company who is completing it.'],
        ['Before hiring someone to finish', 'Confirm their CSLB license and get the permit and inspection status in writing.'],
      ]],
    ],
    'sources' => [
      ['pv magazine: converted to Chapter 7', 'https://pv-magazine-usa.com/2026/09/09/freedom-forever-bankruptcy-converted-to-chapter-7-liquidation-key-dates-set/'],
      ['CSLB: check a license', 'https://www.cslb.ca.gov/OnlineServices/CheckLicenseII/CheckLicense.aspx'],
    ],
  ],
  'titan-solar-out-of-business' => ['_base' => 'orphan',
    'title' => 'Titan Solar Out of Business: What Titan Customers Can Do',
    'desc'  => 'Titan Solar Power stopped operating and filed Chapter 7 in June 2024. What happened to its workmanship warranty, and who to call for your panels, inverter and financing.',
    'kicker' => 'Titan Solar customers',
    'h1'    => 'Titan Solar closed. Is my warranty gone?',
    'lede'  => 'The installer’s warranty is effectively gone. The equipment warranties and your financing are separate.',
    'answer' => 'Titan Solar Power stopped operating on June 13, 2024 and filed for Chapter 7 a week later. In a Chapter 7 the company is liquidated, so its own workmanship warranty has no one behind it. Panel, inverter and battery warranties come from the manufacturers and generally continue. A loan, lease or PPA continues with the finance company.',
    'points' => [
      ['Equipment warranties', 'Find the brand and serial number of the panels and inverters, and contact the manufacturer for failed parts.'],
      ['Financing', 'Keep paying the lender or lease company. They are your contact for anything tied to the agreement.'],
      ['Court information', 'The Arizona bankruptcy court has a Titan page with FAQs in English and Spanish.'],
    ],
    'sources' => [
      ['U.S. Bankruptcy Court, Arizona: Titan FAQs', 'https://www.azb.uscourts.gov/sites/azb/files/Titan%20Solar%20Bankruptcy%20FAQs.pdf'],
      ['pv magazine: Titan closure', 'https://www.pv-magazine.com/2024/06/18/another-u-s-residential-installer-announces-business-closure'],
    ],
  ],
  'mosaic-solar-loan-bankruptcy' => ['_base' => 'orphan',
    'title' => 'Mosaic Solar Loan Bankruptcy: Who Services Your Loan Now?',
    'desc'  => 'Solar lender Mosaic filed Chapter 11 in June 2025. Mosaic loans are now serviced by Solar Servicing, a Forbright Bank subsidiary. What it means for your payments and your system.',
    'kicker' => 'Mosaic solar loans',
    'h1'    => 'Mosaic went bankrupt. Do I still owe my solar loan?',
    'lede'  => 'Yes. Mosaic was the lender, not the installer, and the loan moved to a new servicer with the same terms.',
    'answer' => 'Mosaic, a solar lender, filed for Chapter 11 in June 2025. Its loan servicing moved to Solar Servicing, a subsidiary of Forbright Bank, which said payment terms and schedules stay the same. Your loan is still owed. Mosaic never installed or repaired systems, so service and warranty questions go to your installer or the equipment manufacturer.',
    'points' => [
      ['Payments', 'Pay the servicer named on your latest statement. Terms didn’t change with the transfer.'],
      ['Repairs', 'Not the lender’s job. Use your installer if it’s still operating, or a licensed solar company.'],
      ['Disputes', 'Questions about who holds your loan go to the servicer in writing. Keep copies.'],
    ],
    'sources' => [
      ['Forbright Bank: servicing moves to Solar Servicing', 'https://www.forbrightbank.com/stories/newsroom/solar-mosaics-loan-servicing-operations-transition-to-solar-servicing/'],
      ['EnergySage: Mosaic files for bankruptcy', 'https://www.energysage.com/news/mosaic-files-for-bankruptcy/'],
    ],
  ],
  'sullivan-solar-closed' => ['_base' => 'orphan',
    'title' => 'Sullivan Solar Power Closed: Help for San Diego Sullivan Customers',
    'desc'  => 'San Diego installer Sullivan Solar Power stopped operating in November 2021. What happened to its warranties and how Sullivan customers get their systems serviced now.',
    'kicker' => 'Sullivan Solar customers · San Diego',
    'h1'    => 'Sullivan Solar closed. Who services my system?',
    'lede'  => 'Sullivan was one of San Diego’s best-known installers. Its systems are now five to fifteen years old, which is when inverters start to need attention.',
    'answer' => 'Sullivan Solar Power stopped operating in early November 2021, and the state contractors board later suspended its license. Its own installation warranty has no one behind it. Many Sullivan systems used Panasonic panels; contact the panel and inverter manufacturers with your serial numbers for warranty help, and use a licensed solar company for repairs.',
    'points' => [
      ['Inverter age', 'String inverters often last 10 to 15 years. Many Sullivan-era systems are reaching that window.'],
      ['Panel warranty', 'Check what the manufacturer warranty covers after the installer closed, especially labor.'],
      ['Your NEM date', 'Older systems are nearing the end of their 20-year NEM term. Find your Permission to Operate date.'],
    ],
    'sources' => [
      ['Solar Power World: Sullivan ceases operations', 'https://www.solarpowerworldonline.com/2021/11/sullivan-solar-power-appears-to-cease-operations/'],
      ['East County Magazine: resources for customers', 'https://www.eastcountymagazine.org/left-dark-sullivan-solar-here-are-some-helpful-resources'],
    ],
    'alt' => ['Is your NEM term ending?', '/nem-2-expiring/'],
  ],
  'petersen-dean-bankruptcy' => ['_base' => 'orphan',
    'title' => 'PetersenDean Bankruptcy: What PetersenDean Solar Customers Can Do',
    'desc'  => 'Roofer and solar installer PetersenDean filed Chapter 11 in June 2020, and its customer contracts went to SolarJuice American. Where PetersenDean solar owners get service now.',
    'kicker' => 'PetersenDean customers',
    'h1'    => 'PetersenDean went bankrupt. What about my roof and solar?',
    'lede'  => 'PetersenDean did both roofs and solar, so check both warranties.',
    'answer' => 'PetersenDean (Red Rose, Inc.) filed for Chapter 11 in June 2020 in Nevada. In early 2021 SolarJuice American agreed to take over its consumer contracts. Manufacturer warranties on panels and inverters generally continue. If your roof and solar came from PetersenDean, have both checked together, because roof work around an array affects both warranties.',
    'points' => [
      ['Roof and solar together', 'A leak near the array is a roof question and a solar question. One visit should look at both.'],
      ['Manufacturer warranties', 'Panels and inverters are covered by their makers, not the installer.'],
      ['Reroofing', 'Removing and reinstalling panels for a new roof needs a licensed contractor and should keep your NEM status.'],
    ],
    'sources' => [
      ['Solar Power World: PetersenDean Chapter 11', 'https://www.solarpowerworldonline.com/2020/06/petersen-dean-files-for-chapter-11-bankruptcy'],
      ['Solar Power World: SolarJuice takes over contracts', 'https://www.solarpowerworldonline.com/2021/01/solarjuice-american-to-take-over-consumer-contracts-of-bankrupt-residential-solar-installer-petersen-dean/'],
    ],
  ],
  'sungevity-bankruptcy' => ['_base' => 'orphan',
    'title' => 'Sungevity Bankruptcy: Who Services Old Sungevity Solar Systems?',
    'desc'  => 'Sungevity filed Chapter 11 in March 2017 and was sold. Some financing agreements went to Sunrun. How Sungevity system owners find their servicer and get repairs.',
    'kicker' => 'Sungevity customers',
    'h1'    => 'My system came from Sungevity. Who handles it now?',
    'lede'  => 'Sungevity is long gone, so your statement is the best clue to who owns your agreement today.',
    'answer' => 'Sungevity filed for Chapter 11 in March 2017 and was sold to an investor group, which renamed it Solar Spectrum. Some Sungevity financing agreements were transferred to Sunrun. If you lease or pay a PPA, the company you pay now is your servicer. If you own the system, you hire repairs yourself, and these systems are old enough that inverters are often due.',
    'points' => [
      ['Find who you pay', 'That company owns or services a lease or PPA, and handles repairs on its equipment.'],
      ['Own it?', 'Check your inverter’s age and warranty. Many from that era are past their original coverage.'],
      ['NEM term', 'A system from that era may be within a few years of the end of its 20-year NEM term.'],
    ],
    'sources' => [
      ['Solar Power World: Sungevity files Chapter 11', 'https://www.solarpowerworldonline.com/2017/03/sungevity-files-chapter-11-bankruptcy/'],
      ['Greentech Media: Sunrun now owns some Sungevity systems', 'https://greentechmedia.com/amp/article/sunrun-now-owns-solar-systems-from-sungevity-customers'],
    ],
    'alt' => ['Is your NEM term ending?', '/nem-2-expiring/'],
  ],

  // ─── Error codes ────────────────────────────────────────────────────────
  'solaredge-error-codes' => ['_base' => 'error',
    'title' => 'SolarEdge Error Codes: Isolation Fault, Arc Detected and Red Light',
    'desc'  => 'What SolarEdge inverter errors mean: isolation faults (18x86, 2x19), arc detection (18xC), grid voltage errors and optimizers not reporting, with what is safe to try.',
    'kicker' => 'SolarEdge owners',
    'h1'    => 'What does my SolarEdge error mean?',
    'lede'  => 'SolarEdge codes depend on your model and firmware, so read the exact code off the inverter screen or the SetApp/mySolarEdge app first.',
    'answer' => 'The SolarEdge faults that matter most are isolation faults, where DC current is leaking to ground (shown as 18x86 in SetApp or 2x19 on older screens for single-phase inverters), and arc detection (18xC), where the inverter sensed arcing in the DC wiring and stopped. Both need a qualified technician; an arc fault should never just be reset over and over. Grid voltage errors are often the utility side and may clear on their own.',
    'points' => [
      ['Isolation fault', 'DC wiring, a connector or a panel is leaking to ground. Often worse after rain or morning dew.'],
      ['Arc detected (18xC)', 'Production stops when an arc is sensed. Usually a loose or damaged connector. A fire risk if ignored.'],
      ['Optimizers missing', 'Fewer optimizers reporting than installed means part of the array isn’t producing or talking.'],
    ],
    'sections' => [
      $SAFETY,
      ['What a technician checks', [
        ['Isolation', 'Measures each string’s resistance to ground to find the leaking section, then the connector or panel.'],
        ['Arcing', 'Inspects connectors and wiring on the roof. One bad connector can trip the whole system.'],
        ['Grid', 'Compares the inverter’s voltage log with the utility service. Repeated high-voltage trips can be a utility issue.'],
      ]],
    ],
    'sources' => [
      ['SolarEdge: isolation fault troubleshooting', 'https://knowledge-center.solaredge.com/sites/kc/files/application_note_isolation_fault_troubleshooting.pdf'],
      ['SolarEdge: arc fault detection', 'https://knowledge-center.solaredge.com/sites/kc/files/arc_fault_detection_application_note_na.pdf'],
    ],
  ],
  'enphase-microinverter-errors' => ['_base' => 'error',
    'title' => 'Enphase Microinverter Errors: DC Resistance Low, GFI, Not Reporting',
    'desc'  => 'What Enphase errors mean: DC Resistance Low – Power Off, GFI tripped, and microinverters not reporting in the Enphase app, plus what owners can check before calling.',
    'kicker' => 'Enphase owners',
    'h1'    => 'Why are my Enphase panels offline?',
    'lede'  => 'With Enphase, every panel has its own microinverter, so one problem usually shows up as one or a few panels, not the whole system.',
    'answer' => 'The most common Enphase fault is “DC Resistance Low – Power Off”: the microinverter measured low insulation resistance between the panel wiring and ground and shut itself off. The usual cause is a failing panel or microinverter. An installer can try to clear it from the Enphase installer tools; if it comes back, the panel or microinverter usually needs replacing under warranty. Panels that are simply “not reporting” are often a communication problem at the IQ Gateway.',
    'points' => [
      ['DC Resistance Low', 'One microinverter shut off for safety. If it won’t clear, the panel or micro is usually the cause.'],
      ['GFI tripped', 'A ground fault at that panel. Moisture can cause one-off trips; repeats mean a real fault.'],
      ['Not reporting', 'Check the IQ Gateway: power, internet, and whether it lost connection to the microinverters.'],
    ],
    'sections' => [
      $SAFETY,
      ['Warranty', [
        ['Microinverter warranties are long', 'Enphase microinverters often carry long manufacturer warranties. A failed unit is frequently a warranty claim.'],
        ['Labor', 'The part may be free while the roof visit to swap it is not. Ask before booking.'],
      ]],
    ],
    'sources' => [
      ['Enphase IQ8 installation manual (troubleshooting)', 'https://www.manualslib.com/manual/2631531/Enphase-Iq8.html?page=25'],
      ['Enphase IQ7 manual: clearing DC Resistance Low', 'https://www.manualslib.com/manual/3167688/Enphase-Iq7.html?page=22'],
    ],
  ],
  'sma-inverter-errors' => ['_base' => 'error',
    'title' => 'SMA Sunny Boy Errors: Red LED, Event 3501, Grid Faults Explained',
    'desc'  => 'What SMA Sunny Boy events mean: red LED, insulation events 3501, 3601 and 3701, and grid events, plus what to check and when to call a qualified technician.',
    'kicker' => 'SMA Sunny Boy owners',
    'h1'    => 'My SMA Sunny Boy has a red light. Now what?',
    'lede'  => 'A steady red LED usually means the inverter has stopped feeding power. The event number tells you why.',
    'answer' => 'On SMA inverters, events 3501, 3601 and 3701 point to an insulation or ground fault on the DC side: damaged cable insulation or a bad connector on the panels or wiring. The inverter disconnects from the grid for safety, and it can come and go with rain or dew. Grid events mean the utility voltage or frequency was out of range; event numbers differ by model, so check your manual. Ground faults need a qualified technician with proper test equipment.',
    'points' => [
      ['Events 3501 / 3601 / 3701', 'Insulation fault on the DC side. Find and fix the damaged string before it gets worse.'],
      ['Grid events', 'Utility voltage or frequency out of range. One-off events often clear; repeats need a look.'],
      ['Check the AC breaker', 'SMA’s first step for a grid-loss event is confirming the inverter’s breaker is on.'],
    ],
    'sections' => [$SAFETY],
    'sources' => [
      ['SMA: checking the PV system for ground faults', 'https://manuals.sma.de/STPTL-10-US/en-US/391466379.html'],
      ['SMA: event messages', 'https://manuals.sma.de/STPTL-10-US/en-US/391460235.html'],
    ],
  ],
  'tesla-powerwall-alerts' => ['_base' => 'error',
    'title' => 'Tesla Powerwall Alerts: Gateway Offline, Breaker Open, Solar Limited',
    'desc'  => 'What Tesla Powerwall alerts mean: grid outage, Gateway offline, Breaker Open, Solar Production Limited and Powerwall Disabled, and which ones need an installer.',
    'kicker' => 'Tesla Powerwall owners',
    'h1'    => 'What is my Powerwall alert telling me?',
    'lede'  => 'Some Powerwall alerts are normal. A few mean the system needs a certified installer.',
    'answer' => 'A grid outage alert means the Powerwall is running your home, which is what it’s for. A greyed-out app or a beeping Gateway usually means the Gateway lost its internet connection. “Breaker Open” means the breaker feeding the Powerwall may be off. “Solar Production Limited” can be expected or can mean a problem, so ask your installer. “Powerwall Disabled” needs a Tesla Certified Installer; Powerwall and the Gateway are not owner-serviceable.',
    'points' => [
      ['Normal', 'Grid outage alerts, and the Powerwall disconnecting from an unstable grid to protect your home.'],
      ['Check yourself', 'Internet and Wi-Fi for an offline Gateway; the Powerwall breaker for Breaker Open.'],
      ['Call an installer', 'Powerwall Disabled, Rapid Shutdown Device Off, or any alert that keeps returning.'],
    ],
    'sections' => [
      ['Don’t troubleshoot during an outage', [
        ['Wait for the grid', 'Tesla advises against troubleshooting during an outage or extreme weather. Let the grid come back first.'],
        ['Then check the basics', 'Internet first, then breakers. Note the time each alert appeared for the installer.'],
      ]],
      ['Adding solar or a battery later', [
        ['Your NEM status', 'A battery alone generally doesn’t end NEM 1.0 or 2.0; added panels can. Get it in writing first.'],
      ]],
    ],
    'sources' => [
      ['Tesla: Powerwall system alerts', 'https://www.tesla.com/support/energy/powerwall/own/system-alerts-notifications'],
      ['Tesla: troubleshooting your Powerwall', 'https://www.tesla.com/support/energy/powerwall/own/troubleshooting'],
    ],
  ],
  'solarcity-error-codes' => ['_base' => 'error',
    'title' => 'SolarCity Inverter Errors and Alerts: What They Mean (Tesla Solar)',
    'desc'  => 'SolarCity systems are now supported by Tesla. Find which inverter your SolarCity system has (SolarEdge, Delta, ABB/Power-One or Tesla) and what its errors and Tesla app alerts mean.',
    'kicker' => 'SolarCity and Tesla solar owners',
    'h1'    => 'My SolarCity system shows an error. What now?',
    'lede'  => 'SolarCity became part of Tesla in 2016, but the inverter on your wall may be one of several brands. The brand decides what the error means.',
    'answer' => 'SolarCity systems are now supported by Tesla, and monitoring moved to the Tesla app. SolarCity installed several inverter brands over the years, commonly SolarEdge and Delta, plus ABB/Power-One on some systems; newer Tesla installs use the Tesla Solar Inverter. Read the brand on the inverter’s label first. Ground or isolation faults and arc faults need a qualified technician. If you lease or pay a PPA, Tesla owns the equipment and handles the repair.',
    'points' => [
      ['Lease or PPA', 'Tesla owns the system and handles repairs. Start with Tesla support in the Tesla app.'],
      ['Bought it?', 'You own it. Equipment warranties may cover parts; check what still applies to a system this age.'],
      ['Find the brand', 'Check the label on the inverter. SolarEdge and Tesla have their own guides here.'],
    ],
    'sections' => [
      $SAFETY,
      ['By inverter brand', [
        ['SolarEdge', 'Isolation faults (ground leakage) and arc detection are the ones that matter. See our SolarEdge error guide.'],
        ['ABB / Power-One (Aurora)', '“Riso Low” means low insulation resistance on the DC side, often moisture or damaged wiring. A “grid fail” warning means the inverter could not stay connected to the grid. Code meanings differ by model, so match the exact text on the display to the manual.'],
        ['Delta', 'Write down the exact message and light pattern and compare it with the manual for your model before resetting anything.'],
        ['Tesla Solar Inverter', 'An isolation alert points to a wiring fault on a string. Repeated arc faults can lock the inverter out until an installer inspects it. “Service Required – Fault Detected” in the app means an inverter may have stopped producing: contact Tesla or your installer.'],
      ]],
      ['If the Tesla app shows nothing', [
        ['Check the inverter first', 'If the inverter screen shows power but the app has no data, the system may be producing fine and only the monitoring connection is down.'],
        ['Night is normal', 'Many inverters go dark or sleep after sunset. Check again in midday sun.'],
        ['Use your bill', 'Your utility bill shows what you sent to the grid. A sudden drop is the clearest sign production fell.'],
      ]],
    ],
    'sources' => [
      ['Tesla: system alerts', 'https://www.tesla.com/support/energy/powerwall/own/system-alerts-notifications'],
      ['Tesla: solar inverter troubleshooting', 'https://energylibrary.tesla.com/docs/Public/EnergyStorage/Powerwall/2/TroubleshootingManual/SetupAndInstall/en-us/GUID-5C3664D3-D990-44A2-913C-D7F1844CE778.html'],
      ['Tesla: arc fault lockout', 'https://energylibrary.tesla.com/docs/Public/EnergyStorage/Powerwall/2/TroubleshootingManual/SetupAndInstall/en-us/GUID-73A45665-0B05-4235-A6A5-9020A46E4B61.html'],
      ['pv-tech: Tesla to use its own inverters for SolarCity', 'https://www.pv-tech.org/news/tesla-confirms-plan-to-use-own-inverters-for-solarcity-installs'],
    ],
  ],
];

// A page's own keys win; the shared set fills in the rest.
foreach ($GUIDES as $key => $page) {
  if (empty($page['_base'])) continue;
  $GUIDES[$key] = $page + ($page['_base'] === 'orphan' ? $ORPHAN_BASE : $ERROR_BASE);
  unset($GUIDES[$key]['_base']);
}
return $GUIDES;
