<?php
/**
 * landing.php — the search landing pages: /solar-quote/, /home-battery/,
 * /solar-audit/, /solar-repair/, /high-bill-with-solar/, /solar-true-up-bill/, /nem-2-expiring/,
 * /add-battery-to-existing-solar/, /solar-installer-out-of-business/, /solar-inspection/, plus the guides in
 * landing-guides.php (closed installers, inverter error codes). Each folder's index.php sets $LANDING and
 * requires this file.
 *
 * Optional keys: 'answer', 'sections', 'sources' + 'checked' (plain-HTML answer, Article schema dated
 * by 'checked'), 'crumb' (middle breadcrumb), 'related' + 'related_title' (linked list; with
 * 'itemlist' it is also an ItemList for search engines).
 *
 * What feeds them:
 *   - the copy below (one entry per page in $PAGES)
 *   - cities.php — what's different in each utility territory, for this topic
 *   - Admin → Knowledge — the FAQ section is the listed Knowledge Center answers,
 *     so correcting an answer there corrects every page that shows it
 *   - plans.php — prices (through prices.js)
 * Site look: style.css + audit-book.css + landing.css.
 */
require_once __DIR__ . '/knowledge/_lib.php';
$CITIES = require __DIR__ . '/cities.php';

$PAGES = [
  'solar-quote' => [
    'title' => 'Solar Quote San Diego | Compare Solar & Battery Options',
    'desc'  => 'Get an honest solar or battery quote in San Diego and Orange County, built for 2026: the homeowner tax credit ended, so we compare PPA, prepaid, loan, cash and CEA $0-down side by side.',
    'kicker' => 'Solar & battery quotes · San Diego & Orange County',
    'h1'    => 'A solar quote that\'s honest about 2026.',
    'lede'  => 'The homeowner tax credit ended December 31, 2025, and plenty of quotes haven\'t caught up. Ours start from what actually applies to your home.',
    'points' => [
      ['The 2026 math', 'Buying with cash or a loan no longer gets 30% back. That value now flows through programs where a company owns the system (PPA, prepaid, lease) as a lower price.'],
      ['Every option side by side', 'PPA, prepaid, loan, cash, and CEA\'s $0-down Solar Plus in CEA cities — with the escalators and dealer fees shown, not buried.'],
      ['No pressure', 'Four quick questions. A real person reviews your roof and usage. No credit check, no obligation.'],
    ],
    'cta'  => ['Get my free quote →', '/quote/?option=new'],
    'alt'  => ['Compare your options first', '/options/'],
    'topic' => 'quote',
    'service' => 'Residential solar and battery quotes',
    'faq' => ['wait-homeowners-really-cant-claim-the-federal-tax-credit-anymore', 'prepaid-esa-vs-ppa-vs-cash-the-30-second-version',
              'whats-an-escalator-and-why-do-sales-reps-hide-it', 'what-are-dealer-fees-and-are-they-a-scam', 'how-do-i-know-if-im-getting-a-fair-price-the-ppw-secret',
              'cea-solar-plus-0-down-no-credit-check', 'energy-community-adder-up-to-10-more-off-based-on-your-zip-code', 'what-happens-to-my-solar-agreement-if-i-sell-my-home'],
  ],
  'home-battery' => [
    'title' => 'Home Battery Installation San Diego | Tesla & Enphase Battery',
    'desc'  => 'Home battery installation in San Diego and Orange County: Tesla Powerwall 3, Enphase IQ Battery 10C and FranklinWH. Under net billing, storing your own solar is the strategy — plus backup power and SDCP or CEA programs.',
    'kicker' => 'Home batteries · Tesla · Enphase · FranklinWH',
    'h1'    => 'Under net billing, the battery is the strategy.',
    'lede'  => 'Solar sent to the grid at midday earns little now. A battery keeps that power for the evening, when it\'s worth the most — and keeps your lights on in an outage.',
    'points' => [
      ['Use your own solar at night', 'Store what your panels make at midday and use it from 4 to 9 pm, when SDG&E power costs the most.'],
      ['Backup that fits your home', 'Whole-home or essentials-only — one battery rarely backs up a whole house, and we\'ll say so up front.'],
      ['Programs that change the math', 'SDCP\'s battery rebate, CEA\'s $0-down Solar Plus, and virtual power plant payments, depending on where you live.'],
    ],
    'cta'  => ['Get a battery quote →', '/quote/?option=existing'],
    'alt'  => ['Not sure it pays? Book an audit', '/audit/'],
    'topic' => 'battery',
    'service' => 'Home battery installation (Tesla Powerwall 3, Enphase IQ Battery 10C, FranklinWH)',
    'faq' => ['do-i-actually-need-a-battery-in-california', 'what-does-a-battery-cost-in-2026', 'whats-the-difference-between-net-metering-and-net-billing-nem-3-0', 'will-one-battery-back-up-my-whole-house-the-1-battery-myth',
              'capacity-vs-power-the-highway-analogy-that-makes-batteries-click', 'battery-warranties-the-fine-print-that-actually-matters', 'sdcp-battery-rebate-3-000-to-6-000-per-battery',
              'cea-solar-plus-0-down-no-credit-check', 'sdg-es-secret-1-3-50-kwh-export-window'],
  ],
  'solar-audit' => [
    'title' => 'Solar Audit San Diego | Solar System Performance Analysis',
    'desc'  => 'An independent solar savings audit in San Diego and Orange County: production versus what you were promised, your bills and true-up, rate plan and agreement — from someone who didn\'t sell you the system.',
    'kicker' => 'Solar savings audits · already have solar?',
    'h1'    => 'I\'m not the company that sold you the system. That\'s the point.',
    'lede'  => 'A solar savings audit checks what your system actually makes and saves against what you were promised: production, bills, true-up, rate plan and agreement.',
    'points' => [
      ['Production vs. the promise', 'What your panels make, against what that roof and system size should make.'],
      ['Bills, true-up and rate plan', 'Why you may still be paying SDG&E, and whether a different rate plan fixes part of it.'],
      ['What to do next', 'A straight answer on batteries, repairs or doing nothing — including SDCP and CEA programs you may qualify for.'],
    ],
    'price' => 'savings-audit',
    'cta'  => ['Book a solar audit →', '/audit/'],
    'alt'  => ['Or check it yourself free in lumaria', 'https://www.lumaria.energy/'],
    'topic' => 'audit',
    'service' => 'Independent solar savings audit',
    'faq' => ['what-is-a-solar-health-audit-and-do-i-need-one', 'i-got-a-surprise-true-up-bill-what-happened', 'what-should-i-watch-in-my-monitoring-app-and-when-should-i-worry',
              'what-are-time-of-use-rates-and-why-do-they-matter', 'my-installer-went-out-of-business-am-i-screwed', 'how-much-maintenance-does-solar-actually-need', 'do-i-actually-need-a-battery-in-california'],
  ],
  'solar-repair' => [
    'title' => 'Solar Not Working? Solar Repair & Troubleshooting Help San Diego',
    'desc'  => 'Solar not producing, monitoring offline, an inverter error or a bill that suddenly went up? We find out why, tell you whether it\'s under warranty, and get you a clear path to fixing it — in San Diego and Orange County, even if your installer is gone.',
    'kicker' => 'Solar troubleshooting · already have solar?',
    'h1'    => 'Something\'s off with your solar. Let\'s find out what.',
    'lede'  => 'A bill that crept up, an app that stopped updating, a red light on the inverter. Most of the time it\'s fixable, and often it\'s still under warranty. The first step is knowing what\'s actually wrong.',
    'points' => [
      ['Is it actually broken?', 'Monitoring that went offline, a tripped breaker, one dead inverter, or a rate plan problem that only looks like a production problem. We sort out which.'],
      ['Covered or not', 'Panels and inverters carry long manufacturer warranties that still apply if your installer went out of business. We check what\'s covered before anyone pays for a repair.'],
      ['A straight path to fixed', 'What to repair, what to leave alone, who should do it, and whether a battery or added panels makes sense while someone is on the roof.'],
    ],
    'price' => 'savings-audit',
    'cta'  => ['Book a solar check-up →', '/audit/'],
    'alt'  => ['Or see how it\'s doing free in lumaria', 'https://www.lumaria.energy/'],
    'topic' => 'repair',
    'service' => 'Solar system troubleshooting and repair guidance',
    'faq' => ['what-should-i-watch-in-my-monitoring-app-and-when-should-i-worry', 'my-installer-went-out-of-business-am-i-screwed', 'i-got-a-surprise-true-up-bill-what-happened',
              'how-much-maintenance-does-solar-actually-need', 'microinverters-vs-string-inverters-which-is-right-for-my-roof', 'battery-warranties-the-fine-print-that-actually-matters', 'what-is-a-solar-health-audit-and-do-i-need-one'],
  ],
  // The question solar owners type into Google and ChatGPT. The optional 'answer', 'sections' and
  // 'sources' keys print as plain HTML, so crawlers that don't run JavaScript read the whole answer.
  // SDG&E figures checked 2026-11-22: re-check when the rate tables change.
  'high-bill-with-solar' => [
    'title' => 'Why Is My Electric Bill High With Solar? San Diego & SDG&E',
    'desc'  => 'Have solar and still get a high SDG&E bill or true-up? The seven usual reasons, how to check each one from your bill and monitoring app, and when a battery, repair or rate change helps.',
    'kicker' => 'Already have solar? · San Diego & Orange County',
    'h1'    => 'Why is my electric bill so high if I have solar?',
    'lede'  => 'Usually it is one of seven things, and you can narrow it down with a bill and your monitoring app.',
    'answer' => 'A high bill with solar usually means your home uses more power than it used to, your system makes less than it should, or you buy power at expensive hours (4–9 pm on SDG&E) and get little credit for what you send back. Some charges also remain however well the panels work: SDG&E\'s Base Services Charge is about $24 a month for most homes (less with income-qualified discounts), and solar homes pay it too.',
    'points' => [
      ['Compare production', 'In your monitoring app, compare the last 12 months with the year before. A small yearly decline is normal. A sharp drop points to equipment, shade or dirt.'],
      ['Compare usage', 'Your usage is what you made, plus what you imported, minus what you exported. If that grew, the system may be fine and simply too small for the home now.'],
      ['Read the bill', 'Find your rate plan, your NEM version, your true-up month and the charges that solar credits cannot cover. Those tell you which fix is worth paying for.'],
    ],
    'sections' => [
      ['The seven usual reasons', [
        ['Your usage grew', 'An EV, pool pump, hot tub, sauna, air conditioning, electric water heater or more people at home can add more than the system was designed for.'],
        ['The system makes less', 'A failed inverter or microinverter, a tripped breaker, new shade or heavy dirt. When monitoring goes offline, this can go unnoticed for months.'],
        ['You buy power in the evening', 'Panels make most of their power midday. On SDG&E the peak runs 4–9 pm, so evening use is bought at the highest rate unless a battery covers it.'],
        ['Exports earn less than imports cost', 'On the Solar Billing Plan (NEM 3.0), power sent to the grid is usually credited well below what you pay to buy it back.'],
        ['Some charges cannot be offset', 'The Base Services Charge and certain non-bypassable charges remain. On the Solar Billing Plan, export credits cannot pay the Base Services Charge.'],
        ['A true-up added up quietly', 'Many NEM 1.0 and 2.0 homes settle energy charges once a year, so small monthly shortfalls arrive as one large bill.'],
        ['Your plan or program changed', 'NEM 1.0 and 2.0 generally last 20 years from interconnection, and then the account moves to current rules. A rate change or a community choice provider such as SDCP or CEA can also change how the bill reads.'],
      ]],
      ['Which fix fits which cause', [
        ['Production dropped', 'Check the warranty and repair it before buying panels or a battery.'],
        ['Evening imports are the problem', 'Move flexible loads to midday first, then see whether a battery pays for itself.'],
        ['Usage outgrew the system', 'Price added solar or a battery, and check how either affects your current NEM status before signing.'],
        ['Only fixed charges are left', 'The system may be working well. More panels will not remove those charges.'],
      ]],
    ],
    'sources' => [
      ['SDG&E: Base Services Charge', 'https://www.sdge.com/electric-billing'],
      ['SDG&E: Understanding your solar bill', 'https://www.sdge.com/solar/solar-billing-plan/UnderstandingYourSolarBill'],
      ['SDG&E: Net Energy Metering', 'https://www.sdge.com/residential/savings-center/solar-power-renewable-energy/net-energy-metering'],
    ],
    'checked' => 'November 22, 2026',
    'price' => 'savings-audit',
    'cta'  => ['Have David find the reason →', '/audit/'],
    'alt'  => ['System not producing? Start here', '/solar-repair/'],
    'topic' => 'bill',
    'service' => 'High electric bill review for homes with solar',
    'faq' => ['i-got-a-surprise-true-up-bill-what-happened', 'what-are-time-of-use-rates-and-why-do-they-matter', 'whats-the-difference-between-net-metering-and-net-billing-nem-3-0',
              'what-should-i-watch-in-my-monitoring-app-and-when-should-i-worry', 'whats-a-cca-and-what-are-sdcp-and-cea', 'do-i-actually-need-a-battery-in-california'],
  ],
  'solar-true-up-bill' => [
    'title' => 'Solar True-Up Bill Explained | SDG&E San Diego',
    'desc'  => 'Why a solar true-up bill can be so high on SDG&E, how to tell a production problem from higher usage, and how to avoid the next surprise.',
    'kicker' => 'True-up bills · SDG&E solar',
    'h1'    => 'Why is my solar true-up bill so high?',
    'lede'  => 'A true-up is a year of energy charges and credits settled at once.',
    'answer' => 'On NEM 1.0 and 2.0, SDG&E reconciles your account at the end of each 12-month period. A big true-up means that over the year the power you took from the grid cost more than the credits for what you sent back. The usual causes are higher usage, lower production, or more buying during expensive hours.',
    'points' => [
      ['Get all 12 months', 'Find the true-up statement and the monthly NEM summaries in My Energy Center. One month will not explain a yearly bill.'],
      ['Compare production', 'Put the same 12 months from your monitoring app beside the year before. A sharp drop needs a system check.'],
      ['Compare usage', 'Add what you made and what you imported, then subtract what you exported. That is what the home used.'],
    ],
    'sections' => [
      ['What the result tells you', [
        ['Production fell sharply', 'Look for a failed inverter, a tripped breaker, shade or dirt, and check warranties before paying for a repair.'],
        ['Usage went up', 'An EV, air conditioning, a pool, a spa or more people at home can outgrow a healthy system.'],
        ['Both look normal', 'Look at the rate plan, when you used power and any changes to rates. The same kilowatt-hours can cost more than last year.'],
      ]],
      ['Avoiding the next surprise', [
        ['Check every month', 'Watch the running NEM balance. It is the early warning for the yearly bill.'],
        ['Shift what you can', 'Car charging, laundry and pool pumps are often cheaper outside the 4–9 pm peak.'],
        ['Read both sides of the bill', 'With SDCP or CEA, generation and SDG&E delivery are separate parts. SDCP reported that SDG&E changed how NEM charges display on October 24, 2026, so a generation amount due may now appear. SDCP says it is not a new fee.'],
      ]],
    ],
    'sources' => [
      ['SDG&E: Net Energy Metering', 'https://www.sdge.com/solar/net-energy-metering'],
      ['San Diego Community Power: Net Energy Metering', 'https://sdcommunitypower.org/programs/net-energy-metering/'],
    ],
    'checked' => 'November 22, 2026',
    'price' => 'savings-audit',
    'cta'  => ['Have my true-up checked →', '/audit/'],
    'alt'  => ['All the reasons a solar bill runs high', '/high-bill-with-solar/'],
    'topic' => 'bill',
    'service' => 'Solar true-up bill review',
    'faq' => ['i-got-a-surprise-true-up-bill-what-happened', 'what-are-time-of-use-rates-and-why-do-they-matter', 'what-should-i-watch-in-my-monitoring-app-and-when-should-i-worry', 'whats-a-cca-and-what-are-sdcp-and-cea'],
  ],
  'nem-2-expiring' => [
    'title' => 'When Does NEM 1.0 or 2.0 Expire? San Diego Solar',
    'desc'  => 'NEM 1.0 and 2.0 generally last 20 years from Permission to Operate. How to find your date, what the Solar Billing Plan changes, and how to prepare.',
    'kicker' => 'NEM 1.0 and 2.0 · San Diego',
    'h1'    => 'When does my NEM 1.0 or 2.0 expire?',
    'lede'  => 'It depends on when your system was turned on, not when you bought the house.',
    'answer' => 'NEM 1.0 and 2.0 generally last 20 years from the date the original system received Permission to Operate. A system switched on in March 2007 reaches the end around March 2027. SDG&E then moves the account to the Solar Billing Plan and says it will notify you by email and in My Energy Center first.',
    'points' => [
      ['Find your PTO date', 'Look for the Permission to Operate letter or email, or ask SDG&E for the interconnection date on the account.'],
      ['Bought a solar home?', 'The clock started when the solar was first interconnected. Moving in does not restart it.'],
      ['Confirm the program', 'Your bill or SDG&E can confirm whether you are on NEM 1.0, NEM 2.0 or the Solar Billing Plan.'],
    ],
    'sections' => [
      ['What changes afterward', [
        ['Exports are valued differently', 'Power you send back earns time-based export credits, usually well below the retail price of buying power.'],
        ['Billing is monthly', 'Monthly charges and export credits appear on the bill, and extra credits roll forward.'],
        ['Timing matters more', 'Using your own solar as it is made, or storing it for the evening, is worth more.'],
      ]],
      ['Before your date', [
        ['Do not end it early by accident', 'Adding more solar than the greater of 10% or 1 kW can move you to the Solar Billing Plan early. Get the effect in writing first.'],
        ['Run both bills', 'Price your last 12 months under current rules and under the Solar Billing Plan. That shows whether doing nothing is fine.'],
        ['Check the age of the equipment', 'At 20 years the inverter, roof and warranties may matter as much as the tariff.'],
      ]],
    ],
    'sources' => [
      ['SDG&E: Net Energy Metering', 'https://www.sdge.com/solar/net-energy-metering'],
      ['SDG&E: Solar Billing Plan', 'https://www.sdge.com/solar/solar-billing-plan'],
      ['San Diego Community Power: Net Energy Metering', 'https://sdcommunitypower.org/programs/net-energy-metering/'],
    ],
    'checked' => 'November 22, 2026',
    'price' => 'savings-audit',
    'cta'  => ['Check my NEM options →', '/audit/'],
    'alt'  => ['Thinking about a battery?', '/add-battery-to-existing-solar/'],
    'topic' => 'audit',
    'service' => 'NEM expiration review for solar homeowners',
    'faq' => ['whats-the-difference-between-net-metering-and-net-billing-nem-3-0', 'do-i-actually-need-a-battery-in-california', 'what-are-time-of-use-rates-and-why-do-they-matter', 'what-is-a-solar-health-audit-and-do-i-need-one'],
  ],
  'add-battery-to-existing-solar' => [
    'title' => 'Add a Battery to Existing Solar Without Losing NEM | San Diego',
    'desc'  => 'Can you add a battery to existing solar and keep NEM 1.0 or 2.0? What generally keeps your status, what can end it, and when a battery pays in San Diego.',
    'kicker' => 'Batteries for existing solar',
    'h1'    => 'Can I add a battery to my solar without losing NEM?',
    'lede'  => 'Usually yes, if the project and any rebate are set up correctly.',
    'answer' => 'Adding a battery by itself generally does not end NEM 1.0 or 2.0. Adding solar panels can: more than the greater of 10% or 1 kW moves the account to the Solar Billing Plan. Some rebate terms can also require a tariff change, so get the effect on your NEM status in writing before you sign.',
    'points' => [
      ['Battery only', 'Generally keeps NEM when interconnected under the paired-storage rules.'],
      ['Battery plus panels', 'The size of the added solar is what usually decides it. Check it against the original system.'],
      ['Battery plus rebate', 'Read the program terms. Some, including certain SGIP reservations, can affect NEM status or add participation rules.'],
    ],
    'sections' => [
      ['When a battery tends to pay', [
        ['You buy power in the evening', 'A battery can cover some of SDG&E’s 4–9 pm peak with solar made earlier.'],
        ['You are on the Solar Billing Plan', 'Keeping your solar at home is often worth more than exporting it.'],
        ['You want backup', 'Outage protection has value even when the bill savings alone are modest.'],
      ]],
      ['When to wait', [
        ['Production has dropped', 'Fix the solar first. A battery cannot replace energy the panels are not making.'],
        ['NEM already covers the bill', 'If only fixed charges are left, savings from storage may be small.'],
        ['The numbers are not from your home', 'Ask for a calculation from your own 12 months of hourly Green Button data.'],
      ]],
    ],
    'sources' => [
      ['San Diego Community Power: Net Energy Metering', 'https://sdcommunitypower.org/programs/net-energy-metering/'],
      ['SDG&E: Solar Billing Plan', 'https://www.sdge.com/solar/solar-billing-plan'],
      ['CPUC: net metering decision', 'https://www.cpuc.ca.gov/industries-and-topics/electrical-energy/demand-side-management/customer-generation/nem-revisit'],
    ],
    'checked' => 'November 22, 2026',
    'cta'  => ['Get a battery quote →', '/quote/?option=existing'],
    'alt'  => ['Not sure it pays? Book an audit', '/audit/'],
    'topic' => 'battery',
    'service' => 'Battery review for existing solar',
    'faq' => ['do-i-actually-need-a-battery-in-california', 'what-does-a-battery-cost-in-2026', 'will-one-battery-back-up-my-whole-house-the-1-battery-myth', 'sdcp-battery-rebate-3-000-to-6-000-per-battery'],
  ],
  'solar-installer-out-of-business' => [
    'title' => 'Solar Installer Out of Business? What to Do in San Diego',
    'desc'  => 'Solar company closed? What happens to your warranties, monitoring, lease or PPA, and how to find licensed help for an orphaned solar system in San Diego.',
    'kicker' => 'Orphaned solar systems',
    'h1'    => 'My solar installer went out of business. What do I do?',
    'lede'  => 'Your system keeps working. The job now is to find who covers each part.',
    'answer' => 'When an installer closes, manufacturer warranties on panels, inverters and batteries generally continue, but the installer’s workmanship warranty may have nobody behind it. A lease or PPA is owned by the financing company, so call the company on that agreement before hiring anyone. For work you pay for, use a properly licensed contractor.',
    'points' => [
      ['Gather the papers', 'Find the contract, Permission to Operate, equipment list, serial numbers, warranties and any financing agreement.'],
      ['Secure monitoring', 'Ask the manufacturer to make you the owner on the account and remove the old installer’s access.'],
      ['Find who owns it', 'With a lease or PPA, the system owner usually handles repairs. With cash or a loan, you do.'],
    ],
    'sections' => [
      ['Who to call', [
        ['Not producing', 'The inverter or battery manufacturer first, with the serial number and a screenshot of the error.'],
        ['Lease or PPA', 'The servicer on your agreement or most recent statement, even if a different company installed it.'],
        ['Roof leak or electrical hazard', 'Get qualified help promptly. For smoke, sparking or a burning smell, get clear and call 911.'],
      ]],
      ['Before paying for a repair', [
        ['Check the license', 'Look up the contractor with CSLB and confirm the classification covers solar work.'],
        ['Separate parts from labor', 'A warranty may replace the part while labor and the visit cost extra.'],
        ['Keep your loan current', 'A closed installer does not normally cancel the financing. Speak with the lender before changing payments.'],
      ]],
    ],
    'sources' => [
      ['CSLB: Solar Smart', 'https://www.cslb.ca.gov/consumers/solar_smart/'],
      ['CSLB: check a license', 'https://www.cslb.ca.gov/OnlineServices/CheckLicenseII/CheckLicense.aspx'],
    ],
    'checked' => 'November 22, 2026',
    'price' => 'savings-audit',
    'cta'  => ['Get my system checked →', '/audit/'],
    'alt'  => ['System not producing? Start here', '/solar-repair/'],
    'topic' => 'repair',
    'service' => 'Help for orphaned solar systems',
    'faq' => ['my-installer-went-out-of-business-am-i-screwed', 'what-should-i-watch-in-my-monitoring-app-and-when-should-i-worry', 'battery-warranties-the-fine-print-that-actually-matters', 'how-much-maintenance-does-solar-actually-need'],
  ],
  'solar-inspection' => [
    'title' => 'Solar Inspection San Diego | Independent Solar System Inspection',
    'desc'  => 'An independent on-site solar inspection in San Diego and Orange County: roof penetrations, mounting, wiring, panels, inverters and batteries checked by someone with no workmanship to defend.',
    'kicker' => 'Independent solar inspections',
    'h1'    => 'An inspection from someone with no workmanship to defend.',
    'lede'  => 'If we didn\'t install it, we have no reason to tell you it\'s fine. We check the roof, mounting, wiring, panels, inverters and battery on site, and photograph what we find.',
    'points' => [
      ['Roof & mounting', 'Penetrations, flashing and racking — the places leaks and loose arrays start.'],
      ['Electrical & equipment', 'Wiring, the main panel, inverters and panel-level diagnostics; battery health if you have one.'],
      ['A written report', 'Photos of every finding and a plain list of what\'s urgent, what can wait, and what\'s fine.'],
    ],
    'price' => 'audit-premium',
    'cta'  => ['Book an inspection →', '/services/audit/'],
    'alt'  => ['Just want the savings check? Book an audit', '/audit/'],
    'topic' => 'inspection',
    'service' => 'Independent on-site solar inspection',
    'faq' => ['will-solar-leak-my-roof', 'my-installer-went-out-of-business-am-i-screwed', 'what-should-i-watch-in-my-monitoring-app-and-when-should-i-worry',
              'the-main-panel-upgrade-solars-silent-budget-killer', 'how-much-maintenance-does-solar-actually-need', 'panel-cleaning-diy-or-hire-it-out', 'what-is-a-solar-health-audit-and-do-i-need-one'],
  ],
];

$PAGES += require __DIR__ . '/landing-guides.php';
// The two general pages point into the guides
$PAGES['solar-installer-out-of-business'] += ['related_title' => 'Find your installer', 'related' => $PAGES['closed-solar-installers']['related']];
$PAGES['solar-repair'] += ['related_title' => 'Error code guides', 'related' => $PAGES['solar-error-codes']['related']];

$P = $PAGES[$LANDING ?? ""] ?? null;
if (!$P) { http_response_code(404); exit('Not found'); }
$url = KC_BASE . '/' . $LANDING . '/';
$faq = array_values(array_filter(array_map('kc_find', $P['faq'])));   // only published answers show
$cityNames = [];
foreach ($CITIES as $t) foreach (array_keys($t['cities']) as $c) $cityNames[] = $c;

$schema = [
  ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => $P['service'], 'serviceType' => $P['service'], 'url' => $url,
   'provider' => ['@id' => KC_BASE . '/#business', '@type' => 'LocalBusiness', 'name' => 'Your Energy Best', 'telephone' => '+1-858-786-2204', 'url' => KC_BASE . '/'],
   'areaServed' => array_map(fn($c) => ['@type' => 'Place', 'name' => (stripos($c, 'orange county') !== false ? 'Orange County' : $c) . ', CA'], $cityNames)],
];
if ($faq) $schema[] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn($e) => [
  '@type' => 'Question', 'name' => $e['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => kc_text($e['answer'])]], $faq)];
// Breadcrumbs on every page; an Article (dated) wherever the page answers a question.
$crumbs = [['Home', KC_BASE . '/']];
if (!empty($P['crumb'])) $crumbs[] = [$P['crumb'][0], KC_BASE . $P['crumb'][1]];
$crumbs[] = [$P['kicker'], $url];
$schema[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(
  fn($i, $c) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1]], array_keys($crumbs), $crumbs)];
$checkedIso = !empty($P['checked']) && strtotime($P['checked']) ? date('Y-m-d', strtotime($P['checked'])) : null;
if (!empty($P['answer'])) {
  $org = ['@type' => 'Organization', '@id' => KC_BASE . '/#business', 'name' => 'Your Energy Best', 'url' => KC_BASE . '/', 'logo' => KC_BASE . '/img/logo.png'];
  $schema[] = ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $P['h1'], 'description' => $P['desc'],
    'mainEntityOfPage' => $url, 'author' => $org, 'publisher' => $org, 'inLanguage' => 'en-US']
    + ($checkedIso ? ['dateModified' => $checkedIso] : []);
}
if (!empty($P['itemlist']) && !empty($P['related'])) $schema[] = ['@context' => 'https://schema.org', '@type' => 'ItemList',
  'name' => $P['related_title'] ?? $P['kicker'], 'itemListElement' => array_map(
    fn($i, $r) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $r[0], 'url' => KC_BASE . $r[1]], array_keys($P['related']), $P['related'])];
$priceTag = function ($key, $fallback) { return '<span data-price-of="' . kc_e($key) . '">' . kc_e($fallback) . '</span>'; };
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= kc_e($P['title']) ?></title>
<meta name="description" content="<?= kc_e($P['desc']) ?>">
<link rel="canonical" href="<?= kc_e($url) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Your Energy Best">
<meta property="og:url" content="<?= kc_e($url) ?>">
<meta property="og:title" content="<?= kc_e($P['title']) ?>">
<meta property="og:description" content="<?= kc_e($P['desc']) ?>">
<meta property="og:image" content="https://yourenergybest.com/img/gallery-1770755863-698b9717bd812.jpeg">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/png" href="/img/logo.png">
<link rel="stylesheet" href="/style.css">
<link rel="stylesheet" href="/audit-book.css">
<link rel="stylesheet" href="/landing.css">
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script src="/nav.js?v=20261123b" defer></script>
</head>
<body>

<section class="ab-hero">
  <nav class="lp-crumbs" aria-label="Breadcrumb"><?php foreach ($crumbs as $i => [$name, $href]): ?><?= $i ? ' <span aria-hidden="true">›</span> ' : '' ?><?php if ($i < count($crumbs) - 1): ?><a href="<?= kc_e($href) ?>"><?= kc_e($name) ?></a><?php else: ?><span aria-current="page"><?= kc_e($name) ?></span><?php endif; ?><?php endforeach; ?></nav>
  <span class="eyebrow"><?= kc_e($P['kicker']) ?></span>
  <h1><?= kc_e($P['h1']) ?></h1>
  <p><?= kc_e($P['lede']) ?></p>
</section>

<main class="ab-wrap lp">
  <div class="ab-card lp-cta">
    <a class="btn btn--primary btn--lg" href="<?= kc_e($P['cta'][1]) ?>"><?= kc_e($P['cta'][0]) ?></a>
    <a class="lp-alt" href="<?= kc_e($P['alt'][1]) ?>"><?= kc_e($P['alt'][0]) ?></a>
<?php if (!empty($P['price'])): ?>
    <p class="fine"><?= $P['price'] === 'savings-audit' ? $priceTag('savings-audit', '$219') . ' · 20–45 minutes with David · fee may be applied toward a battery or new system' : $priceTag('audit-premium', '$479') . ' · on site · includes the full savings audit' ?></p>
<?php else: ?>
    <p class="fine">Free · no credit check · no obligation</p>
<?php endif; ?>
  </div>

<?php if (!empty($P['answer'])): ?>
  <section class="ab-card lp-short">
    <h2>The short answer</h2>
    <p><?= kc_e($P['answer']) ?></p>
  </section>
<?php endif; ?>
  <div class="points">
<?php foreach ($P['points'] as $i => [$h, $t]): ?>
    <div class="point"><span class="n"><?= sprintf('%02d', $i + 1) ?></span><h3><?= kc_e($h) ?></h3><p><?= kc_e($t) ?></p></div>
<?php endforeach; ?>
  </div>

<?php foreach ($P['sections'] ?? [] as [$heading, $items]): ?>
  <section class="ab-card lp-body">
    <h2><?= kc_e($heading) ?></h2>
<?php foreach ($items as [$h, $t]): ?>
    <h3><?= kc_e($h) ?></h3>
    <p><?= kc_e($t) ?></p>
<?php endforeach; ?>
  </section>
<?php endforeach; ?>
<?php if (!empty($P['related'])): ?>
  <section class="ab-card lp-related">
    <h2><?= kc_e($P['related_title'] ?? 'Related guides') ?></h2>
    <ul>
<?php foreach ($P['related'] as [$name, $href, $note]): if (rtrim($href, '/') === '/' . $LANDING) continue; ?>
      <li><a href="<?= kc_e($href) ?>"><?= kc_e($name) ?></a><span><?= kc_e($note) ?></span></li>
<?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>
<?php if (!empty($P['sources'])): ?>
  <p class="lp-sources">General information from Your Energy Best; your account and agreement control. Last reviewed <?= kc_e($P['checked']) ?>. Sources:
<?php foreach ($P['sources'] as [$name, $href]): ?>
    <a href="<?= kc_e($href) ?>" target="_blank" rel="noopener"><?= kc_e($name) ?></a>
<?php endforeach; ?>
  </p>
<?php endif; ?>
  <section class="ab-section">
    <h2>Where we work</h2>
    <p class="lp-sub">What's different depends on your utility. Find your city:</p>
<?php foreach ($CITIES as $t): ?>
    <div class="note lp-terr">
      <h3><?= kc_e($t['name']) ?></h3>
      <p class="lp-cities"><?= kc_e(implode(' · ', array_map('ucfirst', array_keys($t['cities'])))) ?></p>
      <p><?= kc_e($t['about'][$P['topic']] ?? '') ?></p>
<?php $local = array_filter($t['cities']); if ($local): ?>
      <ul class="lp-local">
<?php foreach ($local as $city => $note): ?>
        <li><strong><?= kc_e($city) ?>:</strong> <?= kc_e($note) ?></li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </div>
<?php endforeach; ?>
  </section>

<?php if ($faq): ?>
  <section class="ab-section">
    <h2>Straight answers</h2>
<?php foreach ($faq as $e): ?>
    <details class="ab-card lp-faq">
      <summary><?= kc_e($e['question']) ?></summary>
      <div class="lp-answer"><?= kc_html($e['answer']) ?></div>
      <a class="lp-more" href="<?= kc_e(kc_url($e)) ?>">Read this answer on its own page →</a>
    </details>
<?php endforeach; ?>
    <p class="lp-sub"><a href="/knowledge/">More in the Knowledge Center →</a></p>
  </section>
<?php endif; ?>

  <div class="ab-card lp-cta lp-cta--end">
    <h2><?= kc_e($P['cta'][0] === 'Get my free quote →' ? 'Ready to see your real numbers?' : 'Ready when you are.') ?></h2>
    <a class="btn btn--primary btn--lg" href="<?= kc_e($P['cta'][1]) ?>"><?= kc_e($P['cta'][0]) ?></a>
    <p class="contact">Or call or text David · <a href="tel:+18587862204">(858) 786-2204</a></p>
  </div>
</main>

<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div>
        <img src="/img/logo.png" alt="Your Energy Best" class="footer__logo" />
        <p>Solar + battery in San Diego &amp; Orange County.</p>
        <div class="footer__contact"><a href="tel:+18587862204">📞 (858) 786-2204</a><span style="opacity:.8;font-size:.9em;white-space:nowrap;"> · 858-SUN-2204</span></div>
      </div>
      <div class="footer__col"><h5>Get started</h5><a href="/quote/">Solar &amp; battery quote</a><a href="/audit/">Solar audit</a><a href="https://www.lumaria.energy/">lumaria app</a></div>
      <div class="footer__col"><h5>Learn</h5><a href="/knowledge/">Knowledge Center</a><a href="/solar-quote/">Solar quotes</a><a href="/home-battery/">Home batteries</a><a href="/solar-audit/">Solar audits</a><a href="/high-bill-with-solar/">High bill with solar?</a><a href="/solar-true-up-bill/">Big true-up bill?</a><a href="/nem-2-expiring/">NEM expiring?</a><a href="/add-battery-to-existing-solar/">Add a battery to solar</a><a href="/solar-installer-out-of-business/">Installer out of business?</a><a href="/closed-solar-installers/">Closed installers list</a><a href="/solar-error-codes/">Inverter error codes</a><a href="/solar-repair/">Solar not working?</a><a href="/solar-inspection/">Solar inspections</a></div>
    </div>
    <div class="footer__bottom"><span>&copy; <?= date('Y') ?> Your Energy Best. All rights reserved.</span><span><a href="/privacy/">Privacy Policy</a> &nbsp;·&nbsp; <a href="/terms/">Terms of Service</a></span></div>
  </div>
</footer>
<script src="/prices.js" defer></script>
</body>
</html>
