<?php
/**
 * landing-es.php — the Spanish pages (/es/…), rendered by landing.php with 'lang' => 'es'.
 * 'en' names the English page each one mirrors, for hreflang (landing.php links both ways).
 * Same facts as the English pages; written for Spanish speakers, not machine-translated.
 * Checked 2026-11-24.
 */
$ES_BASE = [
  'lang' => 'es',
  'price' => 'savings-audit',
  'cta' => ['Reserve su auditoría gratis →', '/audit/'],
  'alt' => ['¿Prefiere hablar? Llame o envíe un texto al (858) 786-2204', 'tel:+18587862204'],
  'topic' => 'audit',
  'faq' => [],
  'checked' => '24 de noviembre de 2026',
];
$ES_LIST = [
  ['¿Factura alta aunque tiene paneles solares?', '/es/factura-alta-con-solar/', 'Las causas más comunes con SDG&E y cómo revisar cada una.'],
  ['Auditoría solar gratis', '/es/auditoria-solar/', 'Revisamos su producción, su factura y su plan de tarifa. Gratis hasta el 31 de diciembre.'],
  ['Cotización de paneles solares y baterías', '/es/cotizacion-solar/', 'Opciones sin pago inicial y una comparación honesta.'],
  ['Batería para su sistema solar', '/es/bateria-solar/', 'Cuándo vale la pena y qué programas aplican en su ciudad.'],
  ['Su instalador solar cerró', '/es/instalador-solar-cerro/', 'Garantías, monitoreo, contratos de arrendamiento y a quién llamar.'],
];
$ES = [
  'es' => $ES_BASE + [
    'en' => '/',
    'title' => 'Energía Solar en San Diego | Auditoría Solar Gratis | Your Energy Best',
    'desc'  => 'Ayuda con paneles solares y baterías en el condado de San Diego: auditoría solar gratis, facturas altas con solar, cotizaciones sin pago inicial y qué hacer si su instalador cerró.',
    'kicker' => 'Energía solar en San Diego',
    'h1'    => 'Ayuda solar honesta para hogares de San Diego.',
    'lede'  => 'Ya sea que tenga paneles solares o esté pensando instalarlos, empiece por aquí.',
    'answer' => 'Your Energy Best ayuda a los dueños de casa del condado de San Diego con paneles solares y baterías: revisamos sistemas que no ahorran lo prometido, explicamos facturas altas con SDG&E, comparamos opciones sin pago inicial y ayudamos cuando el instalador original cerró. La auditoría solar es gratis hasta el 31 de diciembre.',
    'points' => [
      ['¿Ya tiene solar?', 'Una auditoría muestra lo que su sistema realmente produce y ahorra, y qué lo arreglaría.'],
      ['¿Nuevo en solar?', 'Comparamos PPA, prepago, préstamo, contado y programas locales lado a lado.'],
      ['Sin presión', 'Respuestas claras. Usted decide.'],
    ],
    'related_title' => 'Páginas en español', 'related' => $ES_LIST, 'itemlist' => true,
    'service' => 'Paneles solares, baterías y auditorías solares en San Diego',
  ],
  'es/factura-alta-con-solar' => $ES_BASE + [
    'en' => '/high-bill-with-solar/',
    'title' => '¿Por Qué Mi Factura Es Alta Si Tengo Paneles Solares? | San Diego',
    'desc'  => 'Las razones más comunes de una factura alta con paneles solares en SDG&E: más consumo, menos producción, horas caras de 4 a 9 pm y el cargo fijo mensual.',
    'kicker' => 'Factura alta con solar',
    'h1'    => '¿Por qué mi factura es alta si tengo paneles solares?',
    'lede'  => 'Casi siempre tiene una explicación. Esta página le ayuda a encontrarla.',
    'answer' => 'Una factura alta con solar casi siempre significa una de tres cosas: su casa usa más electricidad que antes, su sistema produce menos de lo que debería, o compra electricidad en las horas más caras (de 4 a 9 pm con SDG&E) y recibe poco crédito por lo que envía a la red. Además, algunos cargos se pagan aunque los paneles funcionen bien: el cargo básico de servicio de SDG&E es de unos $24 al mes para la mayoría de los hogares, y los hogares con solar también lo pagan.',
    'points' => [
      ['Más consumo', 'Un auto eléctrico, aire acondicionado o más personas en casa cambian la cuenta rápido.'],
      ['Menos producción', 'Un inversor apagado o un panel con falla puede pasar meses sin que nadie lo note.'],
      ['Horas caras', 'De 4 a 9 pm la electricidad cuesta más; ahí es donde una batería ayuda.'],
    ],
    'sections' => [
      ['Cómo revisarlo', [
        ['Compare con el año pasado', 'Si el consumo subió, la causa está en casa, no en los paneles.'],
        ['Revise su app de monitoreo', 'Busque días con producción en cero o un inversor con luz roja.'],
        ['Mire su plan de tarifa', 'Un plan equivocado puede costar cientos de dólares al año.'],
      ]],
    ],
    'sources' => [['SDG&E: entender su factura solar', 'https://www.sdge.com/solar/solar-billing-plan/UnderstandingYourSolarBill']],
    'service' => 'Auditoría de facturas solares',
  ],
  'es/auditoria-solar' => $ES_BASE + [
    'en' => '/solar-audit/',
    'title' => 'Auditoría Solar Gratis en San Diego | Revise Su Sistema Solar',
    'desc'  => 'Auditoría solar gratis hasta el 31 de diciembre en San Diego: producción, facturas, plan de tarifa, contrato y si una batería le conviene.',
    'kicker' => 'Auditoría solar · Gratis por tiempo limitado',
    'h1'    => 'Descubra lo que su sistema solar realmente le ahorra.',
    'lede'  => 'La auditoría solar es gratis hasta el 31 de diciembre. Sin pago y sin compromiso.',
    'answer' => 'Una auditoría solar compara lo que sus paneles producen con lo que deberían producir, revisa sus facturas y su plan de tarifa con SDG&E, y le dice en lenguaje claro dónde se está perdiendo dinero y qué lo arreglaría. Normalmente cuesta $219; es gratis hasta el 31 de diciembre. Dura de 20 a 45 minutos.',
    'points' => [
      ['Producción', 'Lo que su sistema produce comparado con lo que su techo y su tamaño deberían dar.'],
      ['Facturas y tarifa', 'Su consumo, su plan y su "true-up", con números reales.'],
      ['Qué hacer', 'Reparar, agregar una batería o no hacer nada: una recomendación honesta.'],
    ],
    'service' => 'Auditoría solar',
  ],
  'es/cotizacion-solar' => $ES_BASE + [
    'en' => '/solar-quote/',
    'cta' => ['Pida su cotización gratis →', '/quote/'],
    'price' => '',
    'title' => 'Cotización de Paneles Solares en San Diego | Opciones Sin Pago Inicial',
    'desc'  => 'Cotización honesta de paneles solares y baterías en San Diego para 2026: comparamos PPA, prepago, préstamo, contado y programas locales sin pago inicial.',
    'kicker' => 'Cotizaciones solares',
    'h1'    => 'Una cotización solar honesta para 2026.',
    'lede'  => 'El crédito fiscal federal para dueños de casa terminó el 31 de diciembre de 2025. Nuestras cotizaciones parten de lo que sí aplica a su casa.',
    'answer' => 'En 2026, comprar paneles solares al contado o con préstamo ya no incluye el crédito fiscal del 30% para dueños de casa. Ese valor ahora llega a través de programas donde una empresa es dueña del sistema (PPA, prepago o arrendamiento), como un precio más bajo. Por eso comparamos todas las opciones lado a lado, incluidas las de su ciudad, como CEA Solar Plus sin pago inicial.',
    'points' => [
      ['Todas las opciones', 'PPA, prepago, préstamo, contado y programas locales, con aumentos anuales y cargos a la vista.'],
      ['Sin pago inicial', 'Opciones de $0 de entrada para hogares que califican.'],
      ['Sin presión', 'Unas pocas preguntas. Una persona real revisa su techo y su consumo.'],
    ],
    'service' => 'Cotizaciones de paneles solares y baterías',
  ],
  'es/bateria-solar' => $ES_BASE + [
    'en' => '/home-battery/',
    'title' => 'Batería Solar en San Diego | ¿Vale la Pena? Programas y Reembolsos',
    'desc'  => 'Cuándo vale la pena una batería con paneles solares en San Diego: las horas caras de 4 a 9 pm, el respaldo durante apagones y los programas de SDCP y CEA.',
    'kicker' => 'Baterías solares',
    'h1'    => '¿Me conviene una batería para mis paneles solares?',
    'lede'  => 'Depende de su plan, de su consumo en la tarde y de su ciudad.',
    'answer' => 'Una batería guarda la energía solar del mediodía para usarla de 4 a 9 pm, cuando la electricidad de SDG&E cuesta más, y mantiene encendido lo importante durante un apagón. Agregar solo una batería generalmente no cambia su estado de NEM 1.0 o 2.0. En ciudades de San Diego Community Power hay un reembolso para baterías cargadas con solar, y en ciudades de Clean Energy Alliance existe CEA Solar Plus sin pago inicial.',
    'points' => [
      ['Horas caras', 'La batería cubre la tarde, cuando comprar electricidad cuesta más.'],
      ['Apagones', 'Respaldo para refrigerador, luces, internet y lo que usted elija.'],
      ['Programas locales', 'SDCP y CEA tienen programas distintos según su ciudad.'],
    ],
    'sources' => [['SDCP: Solar Battery Savings', 'https://sdcommunitypower.org/solar-battery-savings/']],
    'service' => 'Baterías para sistemas solares',
  ],
  'es/instalador-solar-cerro' => $ES_BASE + [
    'en' => '/solar-installer-out-of-business/',
    'title' => 'Mi Instalador Solar Cerró: Qué Hacer | San Diego',
    'desc'  => '¿Su compañía solar cerró o se declaró en bancarrota? Qué pasa con sus garantías, su monitoreo, su arrendamiento o PPA, y cómo conseguir ayuda con licencia.',
    'kicker' => 'Instaladores que cerraron',
    'h1'    => 'Mi instalador solar cerró. ¿Qué hago?',
    'lede'  => 'Su sistema sigue funcionando. Lo que cambia es quién cubre cada parte.',
    'answer' => 'Cuando un instalador cierra, las garantías de los fabricantes de paneles, inversores y baterías generalmente siguen vigentes, pero la garantía de mano de obra del instalador puede quedarse sin respaldo. Si tiene un arrendamiento o PPA, el sistema pertenece a la compañía financiera: llámela primero. Para cualquier reparación que pague, use un contratista con licencia de la CSLB.',
    'points' => [
      ['Reúna sus papeles', 'Contrato, permiso para operar (PTO), lista de equipos, números de serie y garantías.'],
      ['Su monitoreo', 'Pida al fabricante que lo ponga a usted como dueño de la cuenta.'],
      ['Siga pagando', 'Un cierre no cancela un préstamo, arrendamiento o PPA.'],
    ],
    'related_title' => 'Compañías que cerraron (en inglés)', 'related' => [
      ['SunPower', '/sunpower-out-of-business/', 'Capítulo 11, agosto de 2024.'],
      ['Sunnova', '/sunnova-bankruptcy/', 'Capítulo 11, junio de 2025.'],
      ['Freedom Forever', '/freedom-forever-bankruptcy/', 'Liquidación (Capítulo 7), agosto de 2026.'],
      ['Lista completa', '/closed-solar-installers/', 'Todas las compañías que seguimos.'],
    ],
    'sources' => [['CSLB: verificar una licencia', 'https://www.cslb.ca.gov/OnlineServices/CheckLicenseII/CheckLicense.aspx']],
    'service' => 'Ayuda para sistemas solares sin instalador',
  ],
];
return $ES;
