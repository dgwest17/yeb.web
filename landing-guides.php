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
$OWNER = [
  ['How the Solar Billing Plan works', '/solar-billing-plan-explained/', 'NEM 3.0: hourly export credits, two credit buckets, the nine-year lock.'],
  ['SDCP battery rebate', '/sdcp-battery-rebate/', 'Who qualifies, the five-year commitment and the 4–9 pm discharge.'],
  ['New roof under solar', '/reroof-with-solar/', 'Remove, reroof, reinstall, and keep your NEM status.'],
  ['Selling a home with solar', '/selling-a-home-with-solar/', 'Owned, financed, leased or PPA: what happens at sale.'],
  ['Lease or PPA buyout', '/solar-lease-buyout/', 'When buyouts apply, how they’re priced, end-of-term choices.'],
  ['Monitoring in your own name', '/transfer-solar-monitoring/', 'Enphase, SolarEdge and Tesla access after an installer or owner change.'],
];
$OWNER_BASE = [
  'price' => 'savings-audit',
  'cta'   => ['Book a free solar audit →', '/audit/'],
  'alt'   => ['More answers in the Knowledge Center', '/knowledge/'],
  'topic' => 'audit',
  'service' => 'Solar owner guidance and audits',
  'crumb' => ['Knowledge Center', '/knowledge/'],
  'related_title' => 'More owner guides',
  'related' => $OWNER,
  'checked' => 'November 24, 2026',
];
$SAFETY = ['Safety first', [
  ['Smoke, sparks, a burning smell or a hot panel', 'Stay clear, and call 911 if anything is burning. Do not open the inverter, unplug connectors or climb on the roof.'],
  ['What you can safely do', 'Read the code, take a photo of the screen or app, and note when it started and the weather. Restart only the way the owner’s manual describes, and only once.'],
  ['When to stop', 'If the same fault comes back, it is telling you something is wrong. Repeated resets can hide a real wiring or insulation problem.'],
]];

$GUIDES = [
  // ─── Hubs ───────────────────────────────────────────────────────────────
  // ─── Audit results (case studies). Initials, city and state only (David, 2026-11-24).
  // Figures are each home's audit and proposal projections; update when installs report real numbers.
  // No prices, payments or break-even (David, 2026-11-24): only what we added, fixed and recovered.
  // Rebate amounts and qualifying for the federal discount are fine to show.
  'solar-audit-results' => [
    'title' => 'Solar Audit Results: Real Southern California Homes, Real Numbers | Your Energy Best',
    'desc'  => 'What solar audits found in real Southern California homes: batteries added to existing solar, non-export add-ons that keep NEM 1.0 and 2.0, a CEA lease, and a NEM 1.0 home preparing for its cliff.',
    'kicker' => 'Audit results',
    'h1'    => 'What we found in real Southern California homes.',
    'lede'  => 'Every home here started with the same audit. Names are left off; each one is a real Southern California household.',
    'answer' => 'Most solar owners we audit have the same problem: the house now uses far more power than when the panels went in, so the old system covers less every year and the true-up grows. The fix depends on the home. Sometimes it is a battery alone, sometimes a non-export add-on that keeps NEM 1.0 or 2.0 untouched, and sometimes it is planning ahead for the end of NEM. Results are projections from each home’s audit; every home’s numbers differ.',
    'points' => [
      ['Same audit, different answers', 'Battery only, add-on panels, a lease, or moving off NEM early: the numbers decide.'],
      ['NEM protected when it should be', 'Non-export designs add panels without touching an existing NEM 1.0 or 2.0 agreement.'],
      ['Free through Dec 31', 'The audit that produced every result on this page is free until the end of the year.'],
    ],
    'sections' => [
      ['A.G. · Encinitas, CA · battery only', [
        ['Before', 'A 5.4 kW system from 2012 and no battery. The home now uses 16,592 kWh a year; the panels make 8,900. Utility bill: about $298 a month.'],
        ['The fix', 'One Tesla Powerwall 3 (13.5 kWh) to carry the evening peak and back up the home. Qualified for the federal prepaid discount.'],
        ['Projected result', 'Monthly utility bill from about $298 to about $118, plus backup power through outages.'],
      ]],
      ['J.L. · Carlsbad, CA · more storage, no SDCP rebate', [
        ['Before', 'An 11 kW system from 2023 with one Powerwall already. The home uses 19,576 kWh a year; the panels make 14,000. Bill: about $263 a month.'],
        ['The fix', 'Two more Powerwall 3s (27 kWh) so the home runs on its own solar through the evening. Carlsbad is a Clean Energy Alliance city, so no SDCP rebate applies.'],
        ['Projected result', 'Monthly utility bill from about $263 to about $114, with virtual power plant income available on top.'],
      ]],
      ['F.B. · Solana Beach, CA · non-export add-on, NEM 1.0 kept', [
        ['Before', 'A 9.5 kW system from 2015 on NEM 1.0. The home uses 22,257 kWh a year. The audit found the system needed repair: it was making about 90% of what it should.'],
        ['The fix', '18 new panels (7.92 kW) designed non-export, plus a Powerwall 3. Nothing new is sent to the grid, so the NEM 1.0 agreement carries on untouched. The existing system’s repair brings it back to full production. Qualified for the federal prepaid discount.'],
        ['Projected result', 'Production up from about 12,300 to about 24,200 kWh a year. Monthly utility bill from about $185 to $0.'],
      ]],
      ['S.K. · San Diego, CA · non-export add-on, NEM 2.0 kept', [
        ['Before', 'A 6.27 kW system from 2018 on NEM 2.0. The home uses 13,789 kWh a year; the panels make 8,957. Bill: about $231 a month.'],
        ['The fix', '11 new panels (4.84 kW) designed non-export, plus a Powerwall 3, keeping NEM 2.0. Qualified for the federal prepaid discount.'],
        ['Projected result', 'Production up from about 8,960 to about 16,600 kWh a year. Monthly utility bill from about $231 to about $24, just the connection charge.'],
      ]],
      ['R.B. · Carlsbad, CA · battery through a CEA lease', [
        ['Before', 'A 4.25 kW system from 2016 on NEM 2.0. Bill: about $104 a month, and no backup power.'],
        ['The fix', 'A Powerwall 3 through Clean Energy Alliance: $0 down, fully warrantied for 25 years, with a buyout option after year 6.'],
        ['Projected result', 'Backup power for the home and its own solar running the evening peak, with a fixed agreement that rises far more slowly than utility rates.'],
      ]],
      ['D.H. · Encinitas, CA · getting ahead of the NEM 1.0 cliff', [
        ['Before', 'Two systems, from 2008 and 2021, still producing everything they should. But the home’s usage has climbed from 4,000 to over 10,000 kWh a year and is still rising, and NEM 1.0 ends in 2028. When it does, the credits from those panels drop sharply and the bill climbs with them.'],
        ['The fix', 'Rather than wait for the cliff, a complete system now: 16 new panels (7.04 kW) and two Powerwall 3s (27 kWh), sized for where usage is heading, while San Diego Community Power’s battery rebate is available ($9,450, applied to the loan) and before borrowing costs rise.'],
        ['Projected result', 'Production up to about 24,500 kWh a year and two batteries to run the evening peak, so the home is insulated from SDG&E rather than exposed when NEM 1.0 ends.'],
      ]],
    ],
    'related_title' => 'Read more', 'related' => [
      ['Book your free audit', '/audit/', 'The same audit behind every result here. Free through December 31.'],
      ['Is my NEM expiring?', '/nem-2-expiring/', 'When your 20 years end and what happens next.'],
      ['Add a battery to existing solar', '/add-battery-to-existing-solar/', 'Keeping NEM, sizing and programs.'],
      ['SDCP battery rebate', '/sdcp-battery-rebate/', 'Who qualifies and how it works.'],
    ],
    'checked' => 'November 24, 2026',
    'price' => 'savings-audit',
    'cta'   => ['Book my free audit →', '/audit/'],
    'alt'   => ['Questions first? Browse the Knowledge Center', '/knowledge/'],
    'topic' => 'audit',
    'service' => 'Solar audits and system upgrades',
    'faq'   => ['what-is-a-solar-health-audit-and-do-i-need-one', 'do-i-actually-need-a-battery-in-california'],
  ],
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
    'desc'  => 'SolarCity inverter errors explained: ABB/Power-One Riso Low, E018, E031, W003; Delta Earth Fault light and errors 506/508; SolarEdge and Tesla alerts. Who fixes it now that SolarCity is Tesla.',
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
        ['ABB / Power-One: “Riso Low”', 'Before connecting each morning, the inverter tests the panels’ insulation to ground and won’t start below its limit (1 MΩ in the manual). Owners often see it only on damp mornings, clearing by midday: that pattern usually points to a wet connector, junction box or damaged cable on the roof, not the inverter. A reading stuck at 0.00 more often means the inverter itself.'],
        ['ABB / Power-One: E018, E031, W003', 'E018 is a leakage-current fault on the DC side. E031 is an output-relay fault inside the inverter, a common failure on older units. W003 is a grid warning: utility voltage or frequency was outside the allowed range, which often clears on its own.'],
        ['Delta: red “Earth Fault” light, errors 506 / 508', 'The red Earth Fault light and errors 506 (while running) or 508 (at start-up) mean an insulation or grounding fault on the panel side, usually worn cable, water in a connector or a damaged panel. Some Delta units keep producing and only warn, so a fault can sit unnoticed for months.'],
        ['Before anyone replaces an inverter', 'An insulation test with a megohmmeter, string by string, tells a roof fault from an inverter fault. Replacing the inverter won’t fix a wet connector.'],
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
      ['Power-One Aurora PVI-3.0/4.2 manual', 'https://esesolar.co.uk/wp-content/uploads/2023/02/AuroraPVI3.0-4.2TL_Manual.pdf'],
      ['Delta Solivia manual: LEDs and faults', 'https://www.manualslib.com/manual/910274/Delta-Solivia-2-5.html?page=19'],
      ['DIY Solar Forum: ABB low Riso in the mornings', 'https://diysolarforum.com/threads/abb-pvi-3-0-inverter-low-riso-in-the-mornings-w-used-solar-panels.27539/'],
    ],
  ],
  // ─── Owner guides (also listed in the Knowledge Center) ─────────────────
  'solar-billing-plan-explained' => ['_base' => 'owner',
    'title' => 'SDG&E Solar Billing Plan Explained (NEM 3.0) | How Export Credits Work',
    'desc'  => 'How SDG&E’s Solar Billing Plan (NEM 3.0 / net billing) works: hourly export credits, the delivery and generation credit buckets, the nine-year price lock, and why a battery matters.',
    'kicker' => 'Solar Billing Plan (NEM 3.0)',
    'h1'    => 'How does SDG&E’s Solar Billing Plan actually work?',
    'lede'  => 'It is not the old net metering. Power you send to the grid earns a credit that depends on the hour, and it’s usually worth less than power you buy.',
    'answer' => 'On SDG&E’s Solar Billing Plan, the power you export earns an hourly credit set from the CPUC’s Avoided Cost Calculator, so the same kilowatt-hour is worth more on some hours than others. Credits come in two buckets, delivery and generation, and each only offsets its own kind of charge. Your export prices are locked for nine years from your Permission to Operate date. Because exports usually earn less than imports cost, using your own solar, often with a battery, is what saves the most.',
    'points' => [
      ['Hourly credits', 'Each exported kWh is credited at that hour’s price, which changes through the day and year.'],
      ['Two buckets', 'Delivery credits offset delivery charges; generation credits offset generation charges.'],
      ['Nine-year lock', 'Your export prices are set for nine years from Permission to Operate.'],
    ],
    'sections' => [
      ['What this means for your bill', [
        ['Use your own power first', 'Running laundry, pool pumps or EV charging while the sun is up uses your solar directly instead of exporting it cheaply.'],
        ['Why batteries fit this plan', 'A battery stores midday solar for the 4–9 pm peak, when buying from the grid costs the most.'],
        ['Fixed charges still apply', 'SDG&E’s Base Services Charge is billed regardless, and export credits can’t offset it.'],
      ]],
      ['If you’re on NEM 1.0 or 2.0', [
        ['You are not on this plan yet', 'Older systems keep their net metering for 20 years from Permission to Operate.'],
        ['What moves you over', 'Adding more solar than allowed, or reaching the end of your 20 years. A battery by itself generally doesn’t.'],
      ]],
    ],
    'sources' => [
      ['SDG&E: Solar Billing Plan', 'https://www.sdge.com/solar/solar-billing-plan'],
      ['SDG&E: export pricing', 'https://www.sdge.com/solar/solar-billing-plan/export-pricing'],
      ['SDCP: Solar Billing Plan', 'https://sdcommunitypower.org/solar-billing-plan/'],
    ],
    'faq' => ['whats-the-difference-between-net-metering-and-net-billing-nem-3-0', 'what-are-time-of-use-rates-and-why-do-they-matter', 'do-i-actually-need-a-battery-in-california'],
  ],
  'sdcp-battery-rebate' => ['_base' => 'owner',
    'title' => 'SDCP Battery Rebate (Solar Battery Savings): Rules and How It Works',
    'desc'  => 'San Diego Community Power’s Solar Battery Savings program: who qualifies, adding a battery to existing solar, the five-year enrollment, the 4–9 pm discharge requirement, and performance payments.',
    'kicker' => 'SDCP battery rebate',
    'h1'    => 'Can I get paid to add a battery with San Diego Community Power?',
    'lede'  => 'If SDCP is your power provider, its Solar Battery Savings program pays an upfront rebate plus ongoing performance payments.',
    'answer' => 'San Diego Community Power’s Solar Battery Savings program pays an upfront rebate and ongoing performance incentives for batteries charged by solar. Only SDCP customers qualify, and NEM and Solar Billing Plan customers can add a new battery to existing solar. In return, the battery must discharge half its usable capacity between 4 and 9 pm on weekdays when called, it can’t be charged from the grid, and you stay enrolled for at least five years or repay part of the rebate.',
    'points' => [
      ['SDCP customers only', 'Check your bill: SDCP shows as your generation provider. San Diego, Chula Vista, Encinitas and more.'],
      ['Existing solar counts', 'NEM and Solar Billing Plan customers can add a battery and qualify.'],
      ['Five-year commitment', 'Leave early and you repay a prorated share: 100% in year one, down to 20% in year five.'],
    ],
    'sections' => [
      ['The rules that matter', [
        ['Solar-charged only', 'The battery must charge fully from your solar, not from the grid.'],
        ['Weekday evening discharge', 'When called, the battery discharges half its usable capacity between 4 and 9 pm on weekdays, so less is held back for your own use.'],
        ['Performance pay', 'Paid for the months you meet the program’s requirements.'],
      ]],
      ['Amounts change', [
        ['Check the current rates', 'Rebate amounts per kWh and funding change over time. We confirm the current figure for your home before you sign.'],
      ]],
    ],
    'sources' => [
      ['SDCP: Solar Battery Savings', 'https://sdcommunitypower.org/solar-battery-savings/'],
      ['SDCP: Solar Battery Savings FAQ', 'https://sdcommunitypower.org/wp-content/uploads/2026/10/Solar-Battery-Savings-Full-FAQ.pdf'],
    ],
    'faq' => ['sdcp-battery-rebate-3-000-to-6-000-per-battery', 'do-i-actually-need-a-battery-in-california', 'what-does-a-battery-cost-in-2026'],
    'alt' => ['Add a battery to existing solar', '/add-battery-to-existing-solar/'],
  ],
  'reroof-with-solar' => ['_base' => 'owner',
    'title' => 'New Roof Under Solar Panels: Remove, Reroof and Reinstall (San Diego)',
    'desc'  => 'Replacing a roof under solar panels: permits, who can remove and reinstall the system, keeping your NEM status, and what to ask SDG&E before the panels come down.',
    'kicker' => 'Reroofing with solar',
    'h1'    => 'I need a new roof, but I have solar. What now?',
    'lede'  => 'The panels come off, the roof goes on, the panels go back. The details decide whether it stays simple.',
    'answer' => 'Replacing a roof under solar means removing the system, reroofing, and reinstalling it, usually with a reroof permit and an electrical permit for the solar work. Have a licensed solar or electrical contractor do the removal and reinstall. Putting back the same system generally keeps your NEM status, while adding panels or making other changes can move you to the Solar Billing Plan, so get SDG&E’s answer in writing before the panels come down.',
    'points' => [
      ['Permits', 'Expect a reroof permit plus an electrical permit for removing and reinstalling the solar.'],
      ['The right contractor', 'A C-46 solar or C-10 electrical contractor should handle the system.'],
      ['Same system, same NEM', 'Like-for-like generally keeps NEM. Changes are where the risk is.'],
    ],
    'sections' => [
      ['Before the panels come down', [
        ['Photograph everything', 'The array, wiring, labels and the inverter, plus a production screenshot, so you can compare afterward.'],
        ['Ask about warranties', 'Removal by someone else can affect workmanship or roof warranties. Ask both sides in writing.'],
        ['Think about timing', 'If an inverter is old, replacing it during the reinstall saves a second visit, but confirm it won’t change your NEM status.'],
      ]],
    ],
    'sources' => [
      ['SDG&E: NEM requirements', 'https://sdge.com/node/4616'],
      ['CSLB: check a license', 'https://www.cslb.ca.gov/OnlineServices/CheckLicenseII/CheckLicense.aspx'],
    ],
    'faq' => ['will-solar-leak-my-roof', 'how-much-maintenance-does-solar-actually-need'],
  ],
  'selling-a-home-with-solar' => ['_base' => 'owner',
    'title' => 'Selling a Home With Solar: Owned, Leased or PPA (California)',
    'desc'  => 'Selling a house with solar panels in California: what changes if the system is owned, financed, leased or on a PPA, transferring the agreement, UCC-1 filings, and what buyers ask for.',
    'kicker' => 'Selling a home with solar',
    'h1'    => 'I’m selling my home. What happens to the solar?',
    'lede'  => 'Owned solar is part of the house. Leased or PPA solar is a contract the buyer takes over, or you settle before closing.',
    'answer' => 'If you own the system outright, it sells with the house. If there’s a solar loan, it usually has to be paid off or handled in escrow. With a lease or PPA, the usual path is transferring the agreement to the buyer, who typically needs to pass the solar company’s credit check; if that doesn’t work, you can buy out or prepay the contract. Start early: solar companies often file a UCC-1 notice on the equipment, and clearing or assigning it takes time.',
    'points' => [
      ['Owned', 'Hand over warranties, the equipment list, monitoring access and recent production.'],
      ['Financed', 'Ask your lender for the payoff and how it’s handled at closing.'],
      ['Lease or PPA', 'Contact the solar company as soon as you list. Transfers take weeks.'],
    ],
    'sections' => [
      ['What buyers and agents ask for', [
        ['Proof it works', 'A year of production and bills, so the savings are real, not a claim.'],
        ['The agreement', 'Payment, escalator, years left and what happens at the end.'],
        ['NEM status', 'Whether the system is on NEM 1.0, 2.0 or the Solar Billing Plan, and when that ends.'],
      ]],
    ],
    'sources' => [
      ['Solar United Neighbors: selling your solar home', 'https://solarunitedneighbors.org/gd-selling-your-solar-home'],
      ['Tesla: solar and property title', 'https://tesla.com/support/energy/solar-panels/documents/property-title'],
    ],
    'faq' => ['my-installer-went-out-of-business-am-i-screwed', 'battery-warranties-the-fine-print-that-actually-matters'],
  ],
  'solar-lease-buyout' => ['_base' => 'owner',
    'title' => 'Solar Lease or PPA Buyout: Options, End of Term and Early Purchase',
    'desc'  => 'Buying out a solar lease or PPA: when buyout options usually apply, fair market value pricing, end-of-term choices, early termination costs, and what to ask your solar company.',
    'kicker' => 'Lease and PPA buyouts',
    'h1'    => 'Should I buy out my solar lease or PPA?',
    'lede'  => 'Sometimes it makes sense, especially when selling or when payments are climbing. Your agreement sets the price and the timing.',
    'answer' => 'Most leases and PPAs allow a buyout at set points in the contract and at the end of the term, usually priced at the system’s fair market value as the agreement defines it. At the end of the term you can typically renew, have the system removed, or buy it. Ending early without a buyout option can mean an early termination fee. Ask the solar company for a written buyout quote and compare it with what the system will save over the years you’ll stay.',
    'points' => [
      ['Find the clause', 'Look for “purchase option,” “buyout” or “end of term” in your agreement.'],
      ['Get it in writing', 'Ask for a dated buyout quote, including any fees.'],
      ['Run the numbers', 'Compare the price with remaining payments and the system’s age and warranties.'],
    ],
    'sections' => [
      ['Before you buy', [
        ['Check the equipment', 'An inverter near the end of its life changes what the system is worth. An audit tells you.'],
        ['Warranties after a buyout', 'Ask which warranties and monitoring transfer to you, and which end with the contract.'],
      ]],
    ],
    'sources' => [
      ['Palmetto: what happens at the end of a lease or PPA', 'https://palmetto.com/solar/what-happens-at-the-end-of-a-solar-lease-or-ppa'],
      ['EnergySage: canceling a solar lease', 'https://energysage.com/solar/solar-leases/cancelling-a-solar-lease-contract'],
    ],
    'faq' => ['prepaid-esa-vs-ppa-vs-cash-the-30-second-version', 'what-is-a-solar-health-audit-and-do-i-need-one'],
  ],
  'transfer-solar-monitoring' => ['_base' => 'owner',
    'title' => 'Get Your Solar Monitoring Into Your Own Name (Enphase, SolarEdge, Tesla)',
    'desc'  => 'How to get access to your own solar monitoring after an installer closes or you buy a home with solar: Enphase ownership transfer, SolarEdge access, and the Tesla app.',
    'kicker' => 'Solar monitoring access',
    'h1'    => 'How do I get my solar monitoring in my own name?',
    'lede'  => 'Monitoring is how you know the system works. If an old installer or the previous owner controls it, you’re flying blind.',
    'answer' => 'Each manufacturer handles this differently. Enphase has an ownership transfer for homeowners that moves the warranty and gives you access to the system in its app, and it may charge a fee. SolarEdge access is usually tied to the installer’s account, so a new installer often requests the transfer, sometimes for a fee. Tesla systems are monitored in the Tesla app, and ownership changes go through Tesla support. Start with the serial number on the inverter or gateway.',
    'points' => [
      ['Enphase', 'Complete Enphase’s transfer of ownership; you get warranty coverage and app access.'],
      ['SolarEdge', 'Usually moved through an installer account. Ask a current installer to request it.'],
      ['Tesla', 'Transfers are handled by Tesla support for the Tesla app.'],
    ],
    'sections' => [
      ['Why it matters', [
        ['Catch problems early', 'A failed optimizer or offline inverter can cost months of production before a bill shows it.'],
        ['Warranty claims', 'Manufacturers often ask for monitoring data when you file a claim.'],
      ]],
    ],
    'sources' => [
      ['Enphase: transfer ownership', 'https://www4.enphase.com/en-uk/support/transfer-ownership-enphase-system'],
      ['Tesla: transferring ownership', 'https://tesla.com/support/energy/solar-panels/after-installation/transferring-ownership'],
    ],
    'faq' => ['what-should-i-watch-in-my-monitoring-app-and-when-should-i-worry', 'my-installer-went-out-of-business-am-i-screwed'],
    'alt' => ['Installer out of business?', '/solar-installer-out-of-business/'],
  ],
];

// A page's own keys win; the shared set fills in the rest.
foreach ($GUIDES as $key => $page) {
  if (empty($page['_base'])) continue;
  $GUIDES[$key] = $page + ['orphan' => $ORPHAN_BASE, 'error' => $ERROR_BASE, 'owner' => $OWNER_BASE][$page['_base']];
  unset($GUIDES[$key]['_base']);
}
return $GUIDES;
