<?php
/**
 * Category sales funnels ( /lp-{funnel}/ ).
 *
 * Every /lp-{key}/ URL renders a full landing page built for paid and social traffic:
 * hero with live "from" price and model count, popular picks, the full range with
 * price-band filters, a buying guide, how ordering works, a WhatsApp quick-order form,
 * FAQs, store details and links to related ranges.
 *
 * - No WordPress pages, rewrite rules or permalink flush are needed: the request path is
 *   matched here and the funnel template is served with a 200 status.
 * - Copy for each funnel lives in registry() and can be extended with the
 *   `guruexpertpowertools_funnels` filter.
 * - The products shown are LIVE from WooCommerce (prices, stock, images). The "Popular
 *   picks" row can be pinned to specific product IDs per funnel in
 *   WooCommerce > Sales Funnels, without touching code. Headline and subheadline can be
 *   overridden there too, and any funnel can be switched off.
 * - Store policies shown on the funnels (delivery, payment, returns, warranty) are the
 *   same wording as the product pages and policy pages. Keep them in sync: Merchant Center
 *   checks that landing pages and policies agree.
 *
 * @package GuruExpertPowerTools
 */

declare( strict_types = 1 );

namespace GuruExpertPowerTools;

defined( 'ABSPATH' ) || exit;

/**
 * Routes, configures and supplies data to the /lp-{key}/ funnel template.
 */
final class Landing_Funnels {

	/** URL prefix every funnel uses. */
	private const PREFIX = 'lp-';

	/** Option holding admin overrides (pinned product IDs, headlines, disabled flags). */
	public const OPTION = 'gxpt_funnels';

	/** Max products in the "Shop the range" grid. */
	public const GRID_LIMIT = 36;

	/** Resolved funnel for the current request (null = not a funnel). */
	private static ?array $funnel = null;

	/** Whether the current request has already been inspected. */
	private static bool $resolved = false;

	public function hooks(): void {
		add_filter( 'pre_handle_404', array( $this, 'pre_handle_404' ), 10, 2 );
		add_filter( 'template_include', array( $this, 'template_include' ), 99 );
		add_filter( 'pre_get_document_title', array( $this, 'document_title' ), 20 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_action( 'wp_head', array( $this, 'head_meta' ), 4 );
		add_action( 'wp_head', array( $this, 'schema' ), 30 );
		// Rank Math: hand it the funnel's title, description and canonical instead of 404/home values.
		add_filter( 'rank_math/frontend/title', array( $this, 'rm_title' ), 20 );
		add_filter( 'rank_math/frontend/description', array( $this, 'rm_description' ), 20 );
		add_filter( 'rank_math/frontend/canonical', array( $this, 'rm_canonical' ), 20 );
		add_filter( 'rank_math/frontend/robots', array( $this, 'rm_robots' ), 20 );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 60 );
			add_action( 'admin_init', array( $this, 'register_setting' ) );
		}
	}

	/* --------------------------------------------------------------------------
	 * Store facts shown on every funnel. Same wording as the product pages.
	 * ----------------------------------------------------------------------- */

	/**
	 * @return array<string,string>
	 */
	public static function store(): array {
		return (array) apply_filters(
			'guruexpertpowertools_funnel_store',
			array(
				'name'       => 'Guru Expert Power Tools',
				'phone'      => '+254 708 777192',
				'phone_tel'  => '+254708777192',
				'email'      => 'info@guruexpertpowertools.co.ke',
				'address'    => 'Magomano House, 1st Floor, Room 10D, Tom Mboya Street, Nairobi',
				'hours'      => 'Mon - Sat, 9:00am - 5:00pm',
				'map'        => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( 'Magomano House, Tom Mboya Street, Nairobi' ),
				'delivery'   => 'Nairobi: same or next business day. Other towns: 1-5 business days. Delivery is KSh 500 flat countrywide on every order, including bulky items.',
				'payment'    => 'Online orders are paid by M-PESA, or by cash on delivery within Nairobi only. Orders outside Nairobi are paid by M-PESA before dispatch.',
				'returns'    => 'We accept returns of defective products only. Report a defect within 7 days of delivery and we will repair, exchange or refund the item. We do not accept returns of non-defective items, including change of mind.',
				'warranty'   => 'We source our stock from authorised distributors and suppliers, and items are backed by the manufacturer warranty where applicable. Ask us about the warranty terms for a specific product before you buy.',
			)
		);
	}

	/* --------------------------------------------------------------------------
	 * Funnel registry.
	 * ----------------------------------------------------------------------- */

	/**
	 * Alternative URLs that render an existing funnel (canonical points to the main key).
	 *
	 * @return array<string,string>
	 */
	private static function aliases(): array {
		return (array) apply_filters(
			'guruexpertpowertools_funnel_aliases',
			array(
				'breakers'               => 'demolition-breakers',
				'demolition-hammers'     => 'demolition-breakers',
				'vacuums'                => 'vacuum-cleaners',
				'carpet-cleaners'        => 'vacuum-cleaners',
				'car-wash-equipment'     => 'pressure-washers',
				'pumps'                  => 'water-pumps',
				'booster-pumps'          => 'water-pumps',
				'toolsets'               => 'hardware-tools',
				'scales'                 => 'weighing-scales',
				'solar-batteries'        => 'batteries',
				'welders'                => 'welding-machines',
				'inverters'              => 'solar-inverters',
				'angle-grinders'         => 'grinders',
				'generator'              => 'generators',
				'compressors'            => 'air-compressors',
				'agricultural-equipment' => 'farm-machinery',
				'farm-equipment'         => 'farm-machinery',
				'chaff-cutters'          => 'farm-machinery',
				'petrol-engines'         => 'engines',
				'diesel-engines'         => 'engines',
			)
		);
	}

	/**
	 * Funnel definitions. Keys are the part after "lp-".
	 *
	 * Fields: name, cats (product_cat slugs), search (optional keyword inside cats),
	 * image (assets/img/categories/{image}.jpg), eyebrow, headline, sub, benefits[[t,d]],
	 * guide[[t,d]], faqs[[q,a]], related[keys].
	 *
	 * Copy rules (Merchant Center): no "genuine/original/best/cheapest" claims, no fake
	 * urgency or counters, policy wording only from store().
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function registry(): array {
		$f = array(
			'demolition-breakers' => array(
				'name'     => 'Demolition Breakers',
				'cats'     => array( 'demolition-breakers' ),
				'image'    => 'demolition-breakers',
				'eyebrow'  => 'Concrete & demolition',
				'headline' => 'Demolition breakers that make short work of concrete',
				'sub'      => 'Electric breakers for slabs, walls, floors and road work. Compare models by power and price, then order online or on WhatsApp.',
				'benefits' => array(
					array( 'Power for the job', 'From light chiselling to breaking thick slabs, choose by wattage and weight.' ),
					array( 'Chisels and spares', 'Pointed and flat chisels for common models are available from our shop.' ),
					array( 'Advice before you buy', 'Tell us the job and our team will match a breaker to it.' ),
				),
				'guide'    => array(
					array( 'Match the weight to the work', 'Lighter breakers suit tiling and wall chasing. Heavier units are for slabs, foundations and road work.' ),
					array( 'Check the power', 'Higher wattage means faster breaking in thick or reinforced concrete.' ),
					array( 'Confirm the chisel fitting', 'Check the shank type (hex or SDS-Max) so spare chisels fit.' ),
					array( 'Plan for long shifts', 'Look for anti-vibration handles and a carry case if you move between sites.' ),
				),
				'faqs'     => array(
					array( 'Which breaker do I need for a concrete slab?', 'For slabs and foundations a heavier, higher-wattage breaker is usually the right choice. Send us the slab thickness on WhatsApp and we will recommend a model.' ),
					array( 'Do breakers come with chisels?', 'Most models include at least one chisel. The product page lists what is in the box, and spare chisels are available.' ),
					array( 'Can I run a breaker on a generator?', 'Yes, if the generator is sized for the breaker\'s wattage with a margin for start-up. Ask us and we will check the pairing.' ),
				),
				'related'  => array( 'grinders', 'generators', 'hardware-tools', 'welding-machines' ),
			),
			'vacuum-cleaners' => array(
				'name'     => 'Vacuum Cleaners & Carpet Cleaners',
				'cats'     => array( 'vacuum-cleaners' ),
				'image'    => 'car-wash-equipment',
				'eyebrow'  => 'Wet & dry cleaning',
				'headline' => 'Wet and dry vacuums and carpet cleaners for every clean-up',
				'sub'      => 'From compact 20-litre units to 100-litre stainless steel machines and spray-extraction carpet cleaners, for homes, offices, car washes and cleaning businesses.',
				'benefits' => array(
					array( 'Wet and dry in one', 'Pick up dust, debris and spills with the same machine.' ),
					array( 'Sizes from 20 to 100 litres', 'Compact units for homes, large drums for commercial cleaning.' ),
					array( 'Carpet cleaners', 'Spray-and-extract machines for sofas, carpets and car interiors.' ),
				),
				'guide'    => array(
					array( 'Pick the tank size', '20-30 litres suits homes and offices. 50-100 litres suits car washes, hotels and cleaning crews.' ),
					array( 'Vacuum or carpet cleaner?', 'A wet and dry vacuum picks up dirt and liquid. A carpet cleaner also sprays solution into fabric and extracts it, for deep cleaning.' ),
					array( 'Check the drum', 'Stainless steel drums resist rust and dents in daily commercial use.' ),
					array( 'Think about moving it', 'Castor wheels, a push handle and a long hose make large machines easy to use.' ),
				),
				'faqs'     => array(
					array( 'What is the difference between a vacuum and a carpet cleaner?', 'A wet and dry vacuum sucks up dust and liquid. A carpet cleaner sprays cleaning solution into the fabric and extracts the dirty water in the same pass, for a deeper clean.' ),
					array( 'Which size is right for a car wash business?', 'Busy car washes usually choose 50 litres or more so they empty the drum less often. Tell us your daily volume and we will advise.' ),
					array( 'Can these machines pick up water?', 'Yes. The wet and dry models are designed to pick up both liquid and dry dirt.' ),
				),
				'related'  => array( 'pressure-washers', 'air-compressors', 'generators' ),
			),
			'pressure-washers' => array(
				'name'     => 'Pressure Washers',
				'cats'     => array( 'pressure-washers', 'car-wash-equipment' ),
				'image'    => 'pressure-washers',
				'eyebrow'  => 'Car wash & cleaning',
				'headline' => 'Pressure washers for car washes, compounds and machinery',
				'sub'      => 'Electric and petrol washers for home use and busy car wash businesses, in single and three phase.',
				'benefits' => array(
					array( 'Single and three phase', 'Models for standard home power and for three-phase commercial supply.' ),
					array( 'Built for long hours', 'Larger motors suit car wash businesses that run all day.' ),
					array( 'Less water, more cleaning', 'High pressure cleans faster than a hosepipe and bucket.' ),
				),
				'guide'    => array(
					array( 'Choose the pressure', 'Higher PSI removes heavy mud and grease. Car washes usually choose higher-pressure, larger-motor models.' ),
					array( 'Check your power supply', 'Single-phase models run from normal sockets. Three-phase models need a three-phase supply.' ),
					array( 'Motor size', 'Bigger motors handle longer working hours without overheating.' ),
					array( 'Electric or petrol', 'Electric models need no fuel and run quieter. Petrol models work where there is no power.' ),
				),
				'faqs'     => array(
					array( 'What pressure do I need for a car wash?', 'Most car wash businesses choose higher-pressure electric models with larger motors for all-day use. Send us your expected number of cars per day and we will advise.' ),
					array( 'Do I need three-phase power?', 'Only for the three-phase models. The single-phase models run from a normal single-phase supply.' ),
					array( 'Can a pressure washer draw water from a tank?', 'Most need a steady water supply at the inlet. Tell us how your water is supplied and we will confirm the right setup.' ),
				),
				'related'  => array( 'vacuum-cleaners', 'water-pumps', 'air-compressors', 'generators' ),
			),
			'water-pumps' => array(
				'name'     => 'Water Pumps',
				'cats'     => array( 'water-pumps' ),
				'image'    => 'water-pumps',
				'eyebrow'  => 'Irrigation & water supply',
				'headline' => 'Water pumps for irrigation, boosting and water transfer',
				'sub'      => 'Petrol pumps, booster pumps and more for farms, homes, apartments and sites.',
				'benefits' => array(
					array( 'Petrol and electric', 'Petrol pumps for the field, electric pumps for homes and buildings.' ),
					array( 'Booster pumps', 'Raise weak water pressure for upper floors, showers and taps.' ),
					array( 'Sizing help', 'Tell us your water source and distance and we will size the pump.' ),
				),
				'guide'    => array(
					array( 'Work out the head', 'Head is how high the pump must lift water, plus pipe losses. Pick a pump rated above it.' ),
					array( 'Choose the outlet size', '2-inch pumps suit most small farms. 3-inch pumps move more water for bigger plots and dewatering.' ),
					array( 'Pick the power source', 'Petrol works anywhere. Electric is cheaper to run where power is available.' ),
					array( 'Check the phase', 'Booster pumps come in single and three phase. Match your supply.' ),
				),
				'faqs'     => array(
					array( 'Should I choose a 2-inch or 3-inch pump?', 'A 2-inch pump suits most small farms and tank filling. A 3-inch pump moves more water, for larger irrigation and draining flooded areas.' ),
					array( 'What does "head" mean?', 'Head is the height, in metres, a pump can push water. Choose a pump whose head is above your lift plus pipe length losses.' ),
					array( 'Which pump boosts pressure in a building?', 'A booster pump. Tell us the number of floors and outlets and we will recommend a size.' ),
				),
				'related'  => array( 'farm-machinery', 'engines', 'generators', 'solar-panels' ),
			),
			'hardware-tools' => array(
				'name'     => 'Hardware & Hand Tools',
				'cats'     => array( 'hardware-tools', 'toolsets' ),
				'image'    => 'hardware-tools',
				'eyebrow'  => 'Site, workshop & home',
				'headline' => 'Hardware and hand tools for site, workshop and home',
				'sub'      => 'Toolsets, construction tools and site equipment, from one shop with countrywide delivery.',
				'benefits' => array(
					array( 'One stop for the job', 'Hand tools, toolsets and site equipment in one order.' ),
					array( 'Contractor orders', 'Send your list on WhatsApp for a quote on larger orders.' ),
					array( 'See it in the shop', 'Visit us on Tom Mboya Street to check tools before you buy.' ),
				),
				'guide'    => array(
					array( 'Buy for your trade', 'Choose tools rated for daily work if you use them on site every day.' ),
					array( 'Sets or single tools', 'Toolsets cost less per tool. Single tools fill gaps in a kit.' ),
					array( 'Check the materials', 'Hardened steel and insulated handles last longer and work safer.' ),
					array( 'Keep spares', 'Blades, bits and discs wear out. Order spares with the tool.' ),
				),
				'faqs'     => array(
					array( 'Do you supply contractors and bulk orders?', 'Yes. Send your list on WhatsApp and we will reply with availability and a quote.' ),
					array( 'Can I see the tools before buying?', 'Yes. Our walk-in shop is at Magomano House, Tom Mboya Street, Nairobi, Monday to Saturday.' ),
					array( 'Do you deliver tools outside Nairobi?', 'Yes, countrywide in 1-5 business days for a flat KSh 500.' ),
				),
				'related'  => array( 'grinders', 'demolition-breakers', 'welding-machines', 'air-compressors' ),
			),
			'weighing-scales' => array(
				'name'     => 'Weighing Scales',
				'cats'     => array( 'weighing-scales' ),
				'image'    => 'weighing-scales',
				'eyebrow'  => 'Shops, farms & stores',
				'headline' => 'Weighing scales for shops, farms and stores',
				'sub'      => 'Digital counter, platform and hanging scales for retail, produce and stock weighing.',
				'benefits' => array(
					array( 'Capacities for every job', 'From counter scales to heavy platform scales.' ),
					array( 'Clear digital readouts', 'Easy-to-read displays for fast weighing.' ),
					array( 'Advice on the right model', 'Tell us what you weigh and we will match a scale.' ),
				),
				'guide'    => array(
					array( 'Pick the capacity', 'Choose a maximum load above the heaviest item you weigh.' ),
					array( 'Check the accuracy', 'Smaller divisions give finer readings for small, valuable items.' ),
					array( 'Platform size', 'Make sure your goods fit on the platform or hook.' ),
					array( 'Power', 'Check whether the scale runs on mains, a rechargeable battery or both.' ),
				),
				'faqs'     => array(
					array( 'Which capacity do I need?', 'Choose a capacity above the heaviest load you will weigh. Tell us what you weigh and we will advise.' ),
					array( 'Can I use these scales for trade?', 'Requirements for trade use vary. Ask us about a specific model before you buy.' ),
					array( 'Do the scales run on battery?', 'Many models have a rechargeable battery as well as mains power. The product page lists the power options.' ),
				),
				'related'  => array( 'batteries', 'hardware-tools', 'solar-panels' ),
			),
			'batteries' => array(
				'name'     => 'Batteries',
				'cats'     => array( 'batteries' ),
				'image'    => 'batteries',
				'eyebrow'  => 'Solar & backup power',
				'headline' => 'Batteries for solar, backup and power systems',
				'sub'      => 'Batteries to store power for homes, shops and off-grid systems, matched to the inverters and panels we stock.',
				'benefits' => array(
					array( 'Built for storage', 'Batteries for solar and backup systems.' ),
					array( 'Matched systems', 'Pair with the inverters and panels in the same order.' ),
					array( 'Sizing help', 'Send your appliance list and we will size the bank.' ),
				),
				'guide'    => array(
					array( 'Match the voltage', 'Your battery bank voltage (12V, 24V or 48V) must match your inverter.' ),
					array( 'Choose the capacity', 'More amp-hours (Ah) means longer backup for the same load.' ),
					array( 'Pick the chemistry', 'Lead-acid and gel cost less up front. Lithium lasts more cycles and weighs less.' ),
					array( 'Plan the whole system', 'Batteries, inverter and panels should be sized together.' ),
				),
				'faqs'     => array(
					array( 'How many batteries do I need?', 'It depends on your load and how many hours of backup you want. Send us your appliance list on WhatsApp and we will size the bank.' ),
					array( 'Will these batteries work with my inverter?', 'They will if the bank voltage and battery type match the inverter\'s settings. Share your inverter model and we will confirm.' ),
					array( 'How long will a battery last?', 'Battery life depends on the type, how deeply it is discharged and how it is charged. Ask us about the expected life of a specific model.' ),
				),
				'related'  => array( 'solar-inverters', 'solar-panels', 'generators' ),
			),
			'welding-machines' => array(
				'name'     => 'Welding Machines',
				'cats'     => array( 'welding-machines' ),
				'image'    => 'welding-machines',
				'eyebrow'  => 'Fabrication & repairs',
				'headline' => 'Welding machines for fabrication, repairs and site work',
				'sub'      => 'Inverter welders and diesel welder-generators for workshops, gates and grills, and sites without power.',
				'benefits' => array(
					array( 'Inverter welders', 'Compact, efficient machines for workshop and site use.' ),
					array( 'Welder-generators', 'Weld and run tools where there is no mains power.' ),
					array( 'Help choosing', 'Tell us what you weld and we will match the amperage.' ),
				),
				'guide'    => array(
					array( 'Choose the amperage', 'Higher amps handle thicker metal and larger electrodes.' ),
					array( 'Check your power', 'Most inverter welders run on single phase. For remote sites, a welder-generator works without mains power.' ),
					array( 'Pick the process', 'Stick (MMA) welding suits most gate, grill and repair work. MIG suits production and thin sheet.' ),
					array( 'Duty cycle', 'A higher duty cycle means longer welding before the machine needs to rest.' ),
				),
				'faqs'     => array(
					array( 'What size welder do I need for gates and grills?', 'Most gate and grill work uses 2.5-3.2mm electrodes, which a 200A-class inverter welder handles. Tell us about heavier work and we will advise.' ),
					array( 'Can I weld where there is no electricity?', 'Yes, with a diesel welder-generator, which welds and also powers tools and lights.' ),
					array( 'Are cables and holders included?', 'Most welders include an electrode holder and earth clamp. The product page lists what is in the box.' ),
				),
				'related'  => array( 'grinders', 'generators', 'hardware-tools', 'air-compressors' ),
			),
			'solar-panels' => array(
				'name'     => 'Solar Panels',
				'cats'     => array( 'solar-panels' ),
				'image'    => 'solar-panels',
				'eyebrow'  => 'Solar power',
				'headline' => 'Solar panels for homes, farms and businesses',
				'sub'      => 'Panels to cut power bills and run lights, appliances and pumps off-grid, with inverters and batteries from the same shop.',
				'benefits' => array(
					array( 'Range of sizes', 'Panels for small lighting kits up to full home systems.' ),
					array( 'Complete systems', 'Inverters and batteries available in the same order.' ),
					array( 'Sizing help', 'Send your appliance list and we will size the system.' ),
				),
				'guide'    => array(
					array( 'Add up your daily use', 'List your appliances and hours of use to estimate the energy you need.' ),
					array( 'Choose panel wattage', 'Higher-watt panels need fewer panels and less roof space for the same output.' ),
					array( 'Check your space', 'Measure the roof or ground area and check for shade.' ),
					array( 'Match the system', 'Panels, inverter and batteries must be sized to work together.' ),
				),
				'faqs'     => array(
					array( 'How many panels do I need?', 'It depends on your daily power use and the panel size. Send us your appliance list on WhatsApp and we will size it.' ),
					array( 'Can solar panels run a water pump?', 'Yes, with a suitable solar pump or an inverter sized for the pump. Ask us about your setup.' ),
					array( 'Do you install?', 'We supply the equipment. Ask us about installation options when you order.' ),
				),
				'related'  => array( 'solar-inverters', 'batteries', 'water-pumps' ),
			),
			'solar-inverters' => array(
				'name'     => 'Solar Inverters',
				'cats'     => array( 'solar-inverters' ),
				'image'    => 'solar-inverters',
				'eyebrow'  => 'Solar & backup power',
				'headline' => 'Solar inverters for reliable home and business power',
				'sub'      => 'Inverters to run your appliances from solar, batteries and the grid.',
				'benefits' => array(
					array( 'Solar, battery and grid', 'Run your loads from whichever source is available.' ),
					array( 'Matched systems', 'Panels and batteries available in the same order.' ),
					array( 'Sizing help', 'Send your appliance list and we will size the inverter.' ),
				),
				'guide'    => array(
					array( 'Size to your load', 'Add up the watts of everything that runs at once, plus a margin for motors.' ),
					array( 'Hybrid or off-grid', 'Hybrid inverters use solar, battery and grid. Off-grid inverters work without grid power.' ),
					array( 'Match the battery voltage', 'The inverter must match your battery bank voltage.' ),
					array( 'Check the solar charger', 'An MPPT charge controller gets more from your panels.' ),
				),
				'faqs'     => array(
					array( 'What size inverter do I need?', 'Add the watts of everything you run at the same time, plus extra for motors and fridges. Send us your list and we will recommend a size.' ),
					array( 'What is a hybrid inverter?', 'A hybrid inverter can draw on solar panels, batteries and the grid, and switches between them automatically.' ),
					array( 'Will it work with my batteries?', 'It will if the battery voltage and type match the inverter\'s settings. Share your battery details and we will confirm.' ),
				),
				'related'  => array( 'solar-panels', 'batteries', 'generators' ),
			),
			'grinders' => array(
				'name'     => 'Angle Grinders',
				'cats'     => array( 'grinders' ),
				'image'    => 'grinders',
				'eyebrow'  => 'Cutting & grinding',
				'headline' => 'Angle grinders for cutting, grinding and polishing',
				'sub'      => 'Grinders for metal, tiles and stone, for fabricators, builders and home workshops.',
				'benefits' => array(
					array( 'Sizes for every job', 'Small grinders for detail work, large ones for heavy cutting.' ),
					array( 'Discs in stock', 'Cutting, grinding and flap discs available from our shop.' ),
					array( 'Help choosing', 'Tell us what you cut and we will match a grinder.' ),
				),
				'guide'    => array(
					array( 'Pick the disc size', '4.5-inch grinders suit general work. 7- and 9-inch grinders cut deeper and faster.' ),
					array( 'Check the power', 'Higher wattage keeps the disc speed up under load.' ),
					array( 'Look for safety features', 'A side handle, guard and lock-on switch make work safer.' ),
					array( 'Corded or cordless', 'Corded grinders run all day. Cordless grinders go where there is no socket.' ),
				),
				'faqs'     => array(
					array( 'Which grinder is best for cutting steel?', 'For general fabrication a 4.5-inch grinder is common. For thick sections a 7- or 9-inch grinder cuts faster. Ask us about your work.' ),
					array( 'Can I cut tiles with a grinder?', 'Yes, with a diamond disc made for tiles or stone.' ),
					array( 'Are discs included?', 'Some models include a disc. The product page lists what is in the box, and discs are sold separately too.' ),
				),
				'related'  => array( 'welding-machines', 'hardware-tools', 'demolition-breakers' ),
			),
			'generators' => array(
				'name'     => 'Generators',
				'cats'     => array( 'generators' ),
				'image'    => 'generators',
				'eyebrow'  => 'Backup & site power',
				'headline' => 'Generators for dependable backup and site power',
				'sub'      => 'Petrol and diesel generators for homes, shops and sites, from compact backup units to key-start, three-phase and open-frame diesel models.',
				'benefits' => array(
					array( 'Key start', 'Most models start with a turn of the key.' ),
					array( 'Petrol and diesel', 'Petrol for occasional backup, diesel for long running hours.' ),
					array( 'Single and three phase', 'Models for homes and for three-phase equipment.' ),
				),
				'guide'    => array(
					array( 'Add up your load', 'List what must run at once. Motors, pumps and fridges need extra power to start.' ),
					array( 'Petrol or diesel', 'Petrol units cost less up front. Diesel units are economical over long hours.' ),
					array( 'Single or three phase', 'Homes and shops need single phase. Three-phase machines need a three-phase generator.' ),
					array( 'Connect it safely', 'Use a changeover switch installed by a qualified electrician.' ),
				),
				'faqs'     => array(
					array( 'What size generator do I need for my home?', 'It depends on what you run at the same time. Lights, TV, fridge and sockets need less than pumps or welders. Send us your appliance list and we will recommend a size.' ),
					array( 'Petrol or diesel?', 'Petrol generators are lighter and cost less to buy. Diesel generators cost more but are economical for long daily running.' ),
					array( 'How do I connect a generator to my house?', 'Through a changeover switch installed by a qualified electrician, never by plugging into a socket.' ),
				),
				'related'  => array( 'welding-machines', 'water-pumps', 'air-compressors', 'solar-inverters' ),
			),
			'air-compressors' => array(
				'name'     => 'Air Compressors',
				'cats'     => array( 'air-compressors' ),
				'image'    => 'air-compressors',
				'eyebrow'  => 'Garages & workshops',
				'headline' => 'Air compressors for garages, spray painting and workshops',
				'sub'      => 'Compact direct-drive compressors and large workshop units, in single and three phase.',
				'benefits' => array(
					array( 'Tank sizes from 25 to 500 litres', 'Compact units for light work, large tanks for busy workshops.' ),
					array( 'Single and three phase', 'Models for normal power and for industrial supply.' ),
					array( 'Help choosing', 'Tell us your tools and we will match the compressor.' ),
				),
				'guide'    => array(
					array( 'Pick the tank size', 'Bigger tanks supply air tools for longer between motor cycles.' ),
					array( 'Match your tools', 'Spray guns and impact wrenches use more air than tyre inflators.' ),
					array( 'Direct drive or larger units', 'Direct-drive units are compact and light. Larger units suit all-day workshop use.' ),
					array( 'Check your power', 'The biggest models need a three-phase supply.' ),
				),
				'faqs'     => array(
					array( 'Which compressor is right for spray painting?', 'Spray painting needs steady air, so most painters choose 50 litres or more. Tell us your spray gun and we will advise.' ),
					array( 'Do I need three-phase power?', 'Only for the three-phase models. The others run from a normal single-phase supply.' ),
					array( 'Can a compressor run an impact wrench?', 'Yes, with enough tank size and air delivery. Ask us which model suits your wrench.' ),
				),
				'related'  => array( 'pressure-washers', 'welding-machines', 'generators', 'hardware-tools' ),
			),
			'farm-machinery' => array(
				'name'     => 'Farm Machinery',
				'cats'     => array( 'agricultural-equipment' ),
				'image'    => 'agricultural-equipment',
				'eyebrow'  => 'Farm & dairy',
				'headline' => 'Farm machinery that saves time and labour',
				'sub'      => 'Chaff cutters, choppers, grain mills, sprayers, brush cutters, walking tractors and engines for small and large farms.',
				'benefits' => array(
					array( 'Motor or engine versions', 'Electric models for farms with power, engine models for farms without.' ),
					array( 'Capacities to match', 'Machines sized for a few animals up to large dairy units.' ),
					array( 'Delivered countrywide', 'Bulky machines delivered for the same KSh 500 flat fee.' ),
				),
				'guide'    => array(
					array( 'Match the capacity', 'Choose a chopper or mill rated above your daily volume.' ),
					array( 'Motor or engine', 'Electric motors cost less to run. Engines work anywhere without mains power.' ),
					array( 'Think about spares', 'Blades, belts and screens wear out. Ask about spares when you order.' ),
					array( 'Plan the power', 'Bigger machines need more horsepower. Check the engine or motor rating.' ),
				),
				'faqs'     => array(
					array( 'Should I buy the motor or the engine version?', 'If you have reliable mains power, the electric motor version is cheaper to run. If not, choose the engine version.' ),
					array( 'Which chopper suits my number of cows?', 'Tell us how many animals you feed and we will recommend a capacity.' ),
					array( 'Do you deliver heavy machines?', 'Yes, countrywide in 1-5 business days for a flat KSh 500.' ),
				),
				'related'  => array( 'engines', 'water-pumps', 'generators' ),
			),
			'engines' => array(
				'name'     => 'Petrol & Diesel Engines',
				'cats'     => array( 'agricultural-equipment' ),
				'search'   => 'engine',
				'image'    => 'agricultural-equipment',
				'eyebrow'  => 'Power for your machines',
				'headline' => 'Petrol and diesel engines to power your machines',
				'sub'      => 'Petrol and diesel engines, air- and water-cooled, to power pumps, posho mills, chaff cutters and other farm and workshop machines.',
				'benefits' => array(
					array( 'Petrol and diesel', 'Light petrol engines and economical diesel engines.' ),
					array( 'Air- and water-cooled', 'Air-cooled for simple upkeep, water-cooled for long heavy-duty runs.' ),
					array( 'Help matching', 'Tell us the machine and we will match the horsepower.' ),
				),
				'guide'    => array(
					array( 'Match the horsepower', 'Check the power your machine needs and choose an engine above it.' ),
					array( 'Petrol or diesel', 'Petrol engines are light and cheap. Diesel engines are economical for long daily use.' ),
					array( 'Air- or water-cooled', 'Water-cooled diesel engines suit continuous heavy work like posho mills.' ),
					array( 'Check the shaft and pulley', 'Make sure the shaft and pulley fit your machine\'s drive.' ),
				),
				'faqs'     => array(
					array( 'Is the machine included with the engine?', 'No. The engines are sold on their own, ready to couple to your pump, mill or chaff cutter.' ),
					array( 'Which engine suits a posho mill?', 'Posho mills usually need a larger diesel engine. Tell us the mill size and we will match one.' ),
					array( 'Manual or electric start?', 'The product page lists the starting method for each engine.' ),
				),
				'related'  => array( 'farm-machinery', 'water-pumps', 'generators' ),
			),
		);
		return (array) apply_filters( 'guruexpertpowertools_funnels', $f );
	}

	/* --------------------------------------------------------------------------
	 * Admin overrides.
	 * ----------------------------------------------------------------------- */

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public static function overrides(): array {
		$o = get_option( self::OPTION, array() );
		return is_array( $o ) ? $o : array();
	}

	/**
	 * Parse "12, 34 56" into unique positive ints.
	 *
	 * @return int[]
	 */
	private static function parse_ids( string $raw ): array {
		$ids = array_map( 'absint', preg_split( '/[^0-9]+/', $raw ) ?: array() );
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Per-funnel accessory exclusions and quick sizing tables.
	 *
	 * "exclude": words that, when found in a product title, keep that product out of the
	 * funnel (accessories and spares that would otherwise set a misleading "From" price).
	 * "sizes": typical sizing rows shown with the buying guide (general guidance, labelled so).
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function extras(): array {
		return (array) apply_filters(
			'guruexpertpowertools_funnel_extras',
			array(
				'generators'          => array(
					'exclude'     => array( 'regulator', 'stabilizer', 'stabiliser', 'avr', 'changeover', 'cover', 'carburetor', 'carburettor', 'spark plug' ),
					'sizes_title' => 'Quick sizing guide',
					'sizes'       => array(
						array( 'Lights, TV, phone charging, laptop', '1 - 2.8 kVA' ),
						array( 'Add a fridge or small water pump', '3.8 - 5 kVA' ),
						array( 'Small shop or office, several appliances', '5 - 7.5 kVA' ),
						array( 'Welders, borehole pumps, three-phase machines', '7.5 kVA and above' ),
					),
				),
				'welding-machines'    => array(
					'exclude'     => array( 'electrode', 'electrodes', 'rod', 'rods', 'glass', 'helmet', 'glove', 'gloves', 'mask', 'apron', 'goggles' ),
					'sizes_title' => 'Quick sizing guide',
					'sizes'       => array(
						array( 'Light repairs and thin metal', '120 - 160 A' ),
						array( 'Gates, grills and general fabrication', '200 A' ),
						array( 'Heavy fabrication and thick sections', '250 A and above' ),
						array( 'Sites with no mains power', 'Diesel welder-generator' ),
					),
				),
				'grinders'            => array( 'exclude' => array( 'disc', 'discs', 'disk', 'cup brush', 'flap' ) ),
				'pressure-washers'    => array( 'exclude' => array( 'hose', 'nozzle', 'lance', 'foam', 'gun' ) ),
				'water-pumps'         => array(
					'exclude'     => array( 'pump control', 'pressure switch', 'float switch', 'pipe', 'hose' ),
					'sizes_title' => 'Quick sizing guide',
					'sizes'       => array(
						array( 'Garden watering and tank filling', '1 - 2 inch' ),
						array( 'Small farm irrigation', '2 inch' ),
						array( 'Larger plots and draining flooded areas', '3 inch' ),
						array( 'Weak pressure in a building', 'Booster pump' ),
					),
				),
				'air-compressors'     => array(
					'exclude'     => array( 'hose', 'coupler', 'fitting', 'spray gun', 'blow gun' ),
					'sizes_title' => 'Quick sizing guide',
					'sizes'       => array(
						array( 'Tyre inflation and blowing dust', '25 - 50 litres' ),
						array( 'Spray painting and nail guns', '50 - 100 litres' ),
						array( 'Garage with impact wrenches', '100 - 200 litres' ),
						array( 'Body shops running several tools', '300 litres and above' ),
					),
				),
				'solar-inverters'     => array(
					'exclude'     => array( 'cable', 'breaker', 'bracket' ),
					'sizes_title' => 'Quick sizing guide',
					'sizes'       => array(
						array( 'Lights, TV, phones and laptop', '1 - 1.5 kVA' ),
						array( 'Add a fridge and small appliances', '3 - 3.5 kVA' ),
						array( 'Whole home, including pump or iron', '5 kVA and above' ),
					),
				),
				'solar-panels'        => array( 'exclude' => array( 'bracket', 'mount', 'mounting', 'cable', 'connector' ) ),
				'demolition-breakers' => array( 'exclude' => array( 'chisel', 'chisels' ) ),
				'vacuum-cleaners'     => array( 'exclude' => array( 'filter bag', 'dust bag' ) ),
				'engines'             => array( 'exclude' => array( 'with', 'cutter', 'chopper', 'trowel', 'pump', 'tractor', 'mill', 'vibrator', 'sprayer', 'generator', 'washer' ) ),
			)
		);
	}

	/**
	 * Whether a product title passes the funnel's accessory exclusions.
	 */
	public static function allowed( string $title, array $f ): bool {
		$words = array_filter( array_map( 'strval', (array) ( $f['exclude'] ?? array() ) ) );
		if ( ! $words ) {
			return true;
		}
		$pattern = '/\b(' . implode( '|', array_map( static fn( string $w ): string => preg_quote( $w, '/' ), $words ) ) . ')\b/i';
		return 1 !== preg_match( $pattern, $title );
	}

	/**
	 * Up to four comparison specs read from the product title (kVA, HP, PSI, litres, phase...).
	 *
	 * @return string[]
	 */
	public static function specs( string $title ): array {
		$rules = array(
			'/(\d+(?:\.\d+)?)\s*kva\b/i'                      => '%s kVA',
			'/(\d+(?:\.\d+)?)\s*hp\b/i'                       => '%s HP',
			'/(\d{3,5})\s*psi\b/i'                            => '%s PSI',
			'/(\d{2,3})\s*bar\b/i'                            => '%s bar',
			'/(\d{2,4})\s*(?:l|ltr|ltrs|litres?|liters?)\b/i' => '%s litres',
			'/(\d(?:\.\d+)?)\s*(?:inch|inches|")/i'            => '%s inch',
			'/(\d{2,3})\s*ah\b/i'                             => '%s Ah',
			'/(\d{2,3})\s*a(?:mps?)?\b/i'                     => '%s A',
			'/(\d{3,5})\s*w(?:atts?)?\b/i'                    => '%s W',
		);
		$out = array();
		foreach ( $rules as $re => $fmt ) {
			if ( preg_match( $re, $title, $m ) ) {
				$out[] = sprintf( $fmt, $m[1] );
			}
		}
		if ( preg_match( '/three[\s-]*phase|\b3[\s-]*phase/i', $title ) ) {
			$out[] = 'Three phase';
		} elseif ( preg_match( '/single[\s-]*phase/i', $title ) ) {
			$out[] = 'Single phase';
		}
		if ( preg_match( '/\bdiesel\b/i', $title ) ) {
			$out[] = 'Diesel';
		} elseif ( preg_match( '/\b(petrol|gasoline)\b/i', $title ) ) {
			$out[] = 'Petrol';
		}
		if ( preg_match( '/\b(key|electric)\s*start\b/i', $title ) ) {
			$out[] = 'Key start';
		} elseif ( preg_match( '/\b(manual|recoil)\s*start\b/i', $title ) ) {
			$out[] = 'Manual start';
		}
		return array_slice( array_values( array_unique( $out ) ), 0, 4 );
	}

	/* --------------------------------------------------------------------------
	 * Request resolution.
	 * ----------------------------------------------------------------------- */

	private static function request_key(): string {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( '' !== $home && '/' !== $home && 0 === strpos( $path, $home ) ) {
			$path = substr( $path, strlen( $home ) );
		}
		$path = trim( strtolower( $path ), '/' );
		if ( '' === $path || false !== strpos( $path, '/' ) || 0 !== strpos( $path, self::PREFIX ) ) {
			return '';
		}
		return sanitize_title( substr( $path, strlen( self::PREFIX ) ) );
	}

	/**
	 * Resolve the funnel for this request once.
	 */
	public static function current(): ?array {
		if ( self::$resolved ) {
			return self::$funnel;
		}
		self::$resolved = true;
		if ( is_admin() || ! taxonomy_exists( 'product_cat' ) || ! post_type_exists( 'product' ) ) {
			return null;
		}
		$key = self::request_key();
		if ( '' === $key ) {
			return null;
		}
		self::$funnel = self::build( $key );
		return self::$funnel;
	}

	/**
	 * Build a funnel array for a key (registry, alias, or a plain product category slug).
	 */
	public static function build( string $key ): ?array {
		$registry = self::registry();
		$aliases  = self::aliases();
		$main     = isset( $registry[ $key ] ) ? $key : ( $aliases[ $key ] ?? '' );

		if ( '' !== $main && isset( $registry[ $main ] ) ) {
			$def = $registry[ $main ];
		} else {
			// Unknown key: fall back to a product category with this slug (generic copy).
			$term = get_term_by( 'slug', $key, 'product_cat' );
			if ( ! $term instanceof \WP_Term || $term->count < 1 ) {
				return null;
			}
			$main = $key;
			$def  = array(
				'name'     => $term->name,
				'cats'     => array( $term->slug ),
				'image'    => $term->slug,
				'eyebrow'  => $term->name,
				'headline' => sprintf( '%s with countrywide delivery', $term->name ),
				'sub'      => sprintf( 'Compare %s by price and specification, then order online or on WhatsApp.', strtolower( $term->name ) ),
				'benefits' => array(),
				'guide'    => array(),
				'faqs'     => array(),
				'related'  => array(),
			);
		}

		$ov = self::overrides()[ $main ] ?? array();
		if ( ! empty( $ov['disabled'] ) ) {
			return null;
		}
		$def = wp_parse_args(
			$def,
			array( 'search' => '', 'benefits' => array(), 'guide' => array(), 'faqs' => array(), 'related' => array(), 'image' => '', 'exclude' => array(), 'sizes' => array(), 'sizes_title' => '' )
		);
		$extra = self::extras()[ $main ] ?? array();
		foreach ( array( 'exclude', 'sizes', 'sizes_title' ) as $k ) {
			if ( empty( $def[ $k ] ) && ! empty( $extra[ $k ] ) ) {
				$def[ $k ] = $extra[ $k ];
			}
		}
		if ( ! empty( $ov['headline'] ) ) {
			$def['headline'] = (string) $ov['headline'];
		}
		if ( ! empty( $ov['sub'] ) ) {
			$def['sub'] = (string) $ov['sub'];
		}
		$def['key']      = $main;
		$def['url']      = home_url( '/' . self::PREFIX . $main . '/' );
		$def['pin_ids']  = self::parse_ids( (string) ( $ov['ids'] ?? '' ) );
		$def['title']    = $def['name'];
		return $def;
	}

	/* --------------------------------------------------------------------------
	 * Product data.
	 * ----------------------------------------------------------------------- */

	/**
	 * Shared product query args for a funnel.
	 *
	 * Products without a featured image are left out by default so the funnel looks
	 * finished; they appear automatically once a photo is added. Filter
	 * `guruexpertpowertools_funnel_require_image` to change this.
	 */
	private static function base_args( array $f, array $extra = array() ): array {
		$tax = array(
			'relation' => 'AND',
			array(
				'taxonomy'         => 'product_cat',
				'field'            => 'slug',
				'terms'            => (array) $f['cats'],
				'include_children' => true,
			),
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => array( 'exclude-from-catalog', 'outofstock' ),
				'operator' => 'NOT IN',
			),
		);
		$meta = array();
		if ( (bool) apply_filters( 'guruexpertpowertools_funnel_require_image', true, $f ) ) {
			$meta[] = array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' );
		}
		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'tax_query'           => $tax, // phpcs:ignore WordPress.DB.SlowDBQuery
		);
		if ( $meta ) {
			$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery
		}
		if ( '' !== (string) $f['search'] ) {
			$args['s'] = (string) $f['search'];
		}
		return array_merge( $args, $extra );
	}

	/**
	 * "Popular picks": pinned IDs from WooCommerce > Sales Funnels, else best sellers.
	 *
	 * @return \WC_Product[]
	 */
	public static function picks( array $f, int $limit = 4 ): array {
		$out = array();
		foreach ( (array) $f['pin_ids'] as $id ) {
			$p = wc_get_product( $id );
			if ( $p instanceof \WC_Product && 'publish' === $p->get_status() && $p->is_visible() ) {
				$out[] = $p;
			}
		}
		if ( $out ) {
			return array_slice( $out, 0, 8 );
		}
		$q = new \WP_Query(
			self::base_args(
				$f,
				array(
					'posts_per_page' => $limit * 5,
					'no_found_rows'  => true,
					'fields'         => 'ids',
					'meta_key'       => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
					'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
				)
			)
		);
		foreach ( $q->posts as $id ) {
			if ( count( $out ) >= $limit ) {
				break;
			}
			$p = wc_get_product( (int) $id );
			if ( $p instanceof \WC_Product && self::allowed( $p->get_name(), $f ) ) {
				$out[] = $p;
			}
		}
		return $out;
	}

	/**
	 * Full range for the grid (excluding the picks already shown).
	 *
	 * @param int[] $exclude Product IDs to skip.
	 */
	public static function range( array $f, array $exclude = array() ): array {
		$q   = new \WP_Query(
			self::base_args(
				$f,
				array(
					'posts_per_page' => self::GRID_LIMIT * 3,
					'no_found_rows'  => true,
					'fields'         => 'ids',
					'post__not_in'   => array_map( 'intval', $exclude ),
					'meta_key'       => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
					'orderby'        => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
				)
			)
		);
		$out = array();
		foreach ( $q->posts as $id ) {
			if ( count( $out ) >= self::GRID_LIMIT ) {
				break;
			}
			$p = wc_get_product( (int) $id );
			if ( $p instanceof \WC_Product && self::allowed( $p->get_name(), $f ) ) {
				$out[] = $p;
			}
		}
		return $out;
	}

	/**
	 * Number of live products in the funnel and its lowest price (cached briefly).
	 *
	 * @return array{count:int,min:float}
	 */
	public static function stats( array $f ): array {
		$key = 'gxpt_lp_stats2_' . md5( $f['key'] . '|' . wp_json_encode( $f['cats'] ) . '|' . $f['search'] . '|' . get_option( 'rk_terms_ver', '1' ) );
		$hit = get_transient( $key );
		if ( is_array( $hit ) ) {
			return $hit;
		}
		$q   = new \WP_Query( self::base_args( $f, array( 'posts_per_page' => 300, 'fields' => 'ids', 'no_found_rows' => false ) ) );
		$min = 0.0;
		$count = 0;
		foreach ( $q->posts as $id ) {
			if ( ! self::allowed( (string) get_the_title( (int) $id ), $f ) ) {
				continue;
			}
			++$count;
			$price = (float) get_post_meta( (int) $id, '_price', true );
			if ( $price > 0 && ( 0.0 === $min || $price < $min ) ) {
				$min = $price;
			}
		}
		$out = array( 'count' => $count, 'min' => $min );
		set_transient( $key, $out, 15 * MINUTE_IN_SECONDS );
		return $out;
	}

	/**
	 * Price bands (thresholds) for the filter chips, from the prices on the page.
	 *
	 * @param float[] $prices Prices.
	 * @return float[] Two rounded thresholds, or empty when there are too few products.
	 */
	public static function bands( array $prices ): array {
		$prices = array_values( array_filter( $prices, static fn( $p ) => $p > 0 ) );
		if ( count( $prices ) < 6 ) {
			return array();
		}
		sort( $prices );
		$n     = count( $prices );
		$round = static function ( float $v ): float {
			$step = $v >= 50000 ? 5000 : ( $v >= 10000 ? 1000 : 500 );
			return (float) ( round( $v / $step ) * $step );
		};
		$a = $round( $prices[ (int) floor( $n / 3 ) ] );
		$b = $round( $prices[ (int) floor( 2 * $n / 3 ) ] );
		return ( $a > 0 && $b > $a ) ? array( $a, $b ) : array();
	}

	/**
	 * Hero image URL (theme category art, then the WooCommerce category image).
	 */
	public static function image( array $f ): string {
		foreach ( array_filter( array( (string) $f['image'], (string) ( $f['cats'][0] ?? '' ) ) ) as $slug ) {
			if ( is_readable( GURUEXPERTPOWERTOOLS_DIR . 'assets/img/categories/' . $slug . '.jpg' ) ) {
				return GURUEXPERTPOWERTOOLS_URI . 'assets/img/categories/' . $slug . '.jpg';
			}
		}
		foreach ( (array) $f['cats'] as $slug ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $term instanceof \WP_Term ) {
				$thumb = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
				if ( $thumb ) {
					$src = wp_get_attachment_image_url( $thumb, 'large' );
					if ( $src ) {
						return (string) $src;
					}
				}
			}
		}
		return '';
	}

	/**
	 * "View all" link: the first category's archive.
	 */
	public static function archive_url( array $f ): string {
		$term = get_term_by( 'slug', (string) ( $f['cats'][0] ?? '' ), 'product_cat' );
		if ( $term instanceof \WP_Term ) {
			$link = get_term_link( $term );
			if ( is_string( $link ) ) {
				return '' !== (string) $f['search'] ? add_query_arg( array( 's' => $f['search'], 'post_type' => 'product' ), home_url( '/' ) ) : $link;
			}
		}
		return function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : home_url( '/' );
	}

	/**
	 * Common FAQs appended to every funnel (store policies, verbatim).
	 *
	 * @return array<int,array{0:string,1:string}>
	 */
	public static function common_faqs(): array {
		$s = self::store();
		return array(
			array( 'How soon can I get my order?', $s['delivery'] ),
			array( 'How do I pay?', $s['payment'] ),
			array( 'Is it covered by warranty?', $s['warranty'] ),
			array( 'Can I return it if there is a problem?', $s['returns'] ),
			array( 'Can I see the product before I buy?', sprintf( 'Yes. Visit our walk-in shop at %s, %s.', $s['address'], $s['hours'] ) ),
		);
	}

	/* --------------------------------------------------------------------------
	 * Front-end hooks.
	 * ----------------------------------------------------------------------- */

	/**
	 * @param bool      $preempt  Whether to short-circuit.
	 * @param \WP_Query $wp_query Main query.
	 */
	public function pre_handle_404( $preempt, $wp_query ) {
		if ( $preempt || ! self::current() ) {
			return $preempt;
		}
		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->is_404  = false;
			$wp_query->is_home = false;
		}
		status_header( 200 );
		return true;
	}

	/**
	 * @param string $template Template path.
	 */
	public function template_include( $template ) {
		if ( ! self::current() ) {
			return $template;
		}
		global $wp_query;
		if ( $wp_query instanceof \WP_Query && $wp_query->is_404 ) {
			$wp_query->is_404 = false;
			status_header( 200 );
		}
		$file = GURUEXPERTPOWERTOOLS_DIR . 'landing-funnel.php';
		return is_readable( $file ) ? $file : $template;
	}

	private static function seo_title( array $f ): string {
		return sprintf( '%1$s in Kenya - Prices & Delivery | %2$s', $f['name'], get_bloginfo( 'name' ) );
	}

	private static function seo_description( array $f ): string {
		return wp_trim_words( $f['sub'] . ' Pay by M-PESA or cash on delivery in Nairobi. KSh 500 flat delivery countrywide.', 40, '' );
	}

	/**
	 * @param string $title Title.
	 */
	public function document_title( $title ) {
		$f = self::current();
		return $f ? self::seo_title( $f ) : $title;
	}

	/** @param mixed $v */
	public function rm_title( $v ) {
		$f = self::current();
		return $f ? self::seo_title( $f ) : $v;
	}

	/** @param mixed $v */
	public function rm_description( $v ) {
		$f = self::current();
		return $f ? self::seo_description( $f ) : $v;
	}

	/** @param mixed $v */
	public function rm_canonical( $v ) {
		$f = self::current();
		return $f ? $f['url'] : $v;
	}

	/** @param mixed $robots */
	public function rm_robots( $robots ) {
		if ( self::current() && is_array( $robots ) ) {
			$robots['index']  = 'index';
			$robots['follow'] = 'follow';
		}
		return $robots;
	}

	/**
	 * Meta description, canonical and Open Graph when Rank Math is not handling them.
	 */
	public function head_meta(): void {
		$f = self::current();
		if ( ! $f || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$img = self::image( $f );
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( self::seo_description( $f ) ) );
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $f['url'] ) );
		printf( '<meta property="og:type" content="website"><meta property="og:title" content="%1$s"><meta property="og:description" content="%2$s"><meta property="og:url" content="%3$s">' . "\n", esc_attr( self::seo_title( $f ) ), esc_attr( self::seo_description( $f ) ), esc_url( $f['url'] ) );
		if ( '' !== $img ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $img ) );
		}
	}

	/**
	 * BreadcrumbList + ItemList + FAQPage JSON-LD for the funnel.
	 */
	public function schema(): void {
		$f = self::current();
		if ( ! $f ) {
			return;
		}
		$picks = self::picks( $f );
		$items = array();
		foreach ( $picks as $i => $p ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'url' => $p->get_permalink(), 'name' => $p->get_name() );
		}
		$faqs = array();
		foreach ( array_merge( (array) $f['faqs'], self::common_faqs() ) as $qa ) {
			$faqs[] = array( '@type' => 'Question', 'name' => $qa[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $qa[1] ) );
		}
		$graph = array(
			array(
				'@type'           => 'BreadcrumbList',
				'itemListElement' => array(
					array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url( '/' ) ),
					array( '@type' => 'ListItem', 'position' => 2, 'name' => $f['name'], 'item' => $f['url'] ),
				),
			),
		);
		if ( $items ) {
			$graph[] = array( '@type' => 'ItemList', 'name' => $f['name'], 'itemListElement' => $items );
		}
		if ( $faqs ) {
			$graph[] = array( '@type' => 'FAQPage', 'mainEntity' => $faqs );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public function enqueue(): void {
		if ( ! self::current() ) {
			return;
		}
		wp_enqueue_style( 'guruexpertpowertools-funnel', GURUEXPERTPOWERTOOLS_URI . 'assets/css/funnel.css', array( 'guruexpertpowertools-industrial' ), GURUEXPERTPOWERTOOLS_VERSION );
		wp_enqueue_script( 'guruexpertpowertools-funnel', GURUEXPERTPOWERTOOLS_URI . 'assets/js/funnel.js', array(), GURUEXPERTPOWERTOOLS_VERSION, true );
		wp_script_add_data( 'guruexpertpowertools-funnel', 'strategy', 'defer' );
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function body_class( $classes ) {
		$f = self::current();
		if ( $f ) {
			$classes   = array_diff( (array) $classes, array( 'error404', 'home', 'blog' ) );
			$classes[] = 'gx-funnel-page';
			$classes[] = 'gx-funnel-' . sanitize_html_class( $f['key'] );
		}
		return $classes;
	}

	/* --------------------------------------------------------------------------
	 * Admin: WooCommerce > Sales Funnels.
	 * ----------------------------------------------------------------------- */

	public function register_setting(): void {
		register_setting(
			'gxpt_funnels_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * @param mixed $input Raw option.
	 * @return array<string,array<string,mixed>>
	 */
	public function sanitize( $input ): array {
		$out = array();
		if ( ! is_array( $input ) ) {
			return $out;
		}
		foreach ( array_keys( self::registry() ) as $key ) {
			$row = isset( $input[ $key ] ) && is_array( $input[ $key ] ) ? $input[ $key ] : array();
			$out[ $key ] = array(
				'ids'      => implode( ', ', self::parse_ids( (string) ( $row['ids'] ?? '' ) ) ),
				'headline' => sanitize_text_field( (string) ( $row['headline'] ?? '' ) ),
				'sub'      => sanitize_textarea_field( (string) ( $row['sub'] ?? '' ) ),
				'disabled' => empty( $row['disabled'] ) ? 0 : 1,
			);
		}
		delete_transient( 'gxpt_lp_admin_counts' );
		return $out;
	}

	public function admin_menu(): void {
		add_submenu_page(
			class_exists( 'WooCommerce' ) ? 'woocommerce' : 'tools.php',
			__( 'Sales Funnels', 'guruexpertpowertools' ),
			__( 'Sales Funnels', 'guruexpertpowertools' ),
			'manage_woocommerce',
			'gxpt-funnels',
			array( $this, 'admin_page' )
		);
	}

	public function admin_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$ov = self::overrides();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sales Funnels', 'guruexpertpowertools' ); ?></h1>
			<p><?php esc_html_e( 'Each funnel is live at its URL. Prices, stock and images always come from the products themselves. Use the fields below to pin the "Popular picks" products (product IDs, in the order you want them, comma separated), change the headline, or switch a funnel off. Leave the IDs empty to show the best sellers automatically. Products without a featured image are hidden from funnels until a photo is added.', 'guruexpertpowertools' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'gxpt_funnels_group' ); ?>
				<table class="widefat striped" style="max-width:1200px">
					<thead><tr>
						<th><?php esc_html_e( 'Funnel', 'guruexpertpowertools' ); ?></th>
						<th><?php esc_html_e( 'Popular picks (product IDs)', 'guruexpertpowertools' ); ?></th>
						<th><?php esc_html_e( 'Headline / subheadline override', 'guruexpertpowertools' ); ?></th>
						<th><?php esc_html_e( 'Off', 'guruexpertpowertools' ); ?></th>
					</tr></thead>
					<tbody>
					<?php
					foreach ( self::registry() as $key => $def ) :
						$row  = $ov[ $key ] ?? array();
						$name = self::OPTION . '[' . $key . ']';
						$url  = home_url( '/' . self::PREFIX . $key . '/' );
						$ids  = self::parse_ids( (string) ( $row['ids'] ?? '' ) );
						?>
						<tr>
							<td style="width:22%">
								<strong><?php echo esc_html( (string) $def['name'] ); ?></strong><br>
								<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( '/' . self::PREFIX . $key . '/' ); ?></a><br>
								<small><?php echo esc_html( implode( ', ', (array) $def['cats'] ) . ( ! empty( $def['search'] ) ? ' + "' . $def['search'] . '"' : '' ) ); ?></small>
							</td>
							<td style="width:30%">
								<input type="text" class="large-text" name="<?php echo esc_attr( $name ); ?>[ids]" value="<?php echo esc_attr( implode( ', ', $ids ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. 31545, 30734, 901085', 'guruexpertpowertools' ); ?>">
								<?php
								foreach ( $ids as $id ) {
									$p = wc_get_product( $id );
									echo '<br><small>' . esc_html( $id . ': ' . ( $p ? $p->get_name() . ( $p->get_image_id() ? '' : ' (no image)' ) . ( 'publish' === $p->get_status() ? '' : ' (not published)' ) : 'not found' ) ) . '</small>';
								}
								?>
							</td>
							<td>
								<input type="text" class="large-text" name="<?php echo esc_attr( $name ); ?>[headline]" value="<?php echo esc_attr( (string) ( $row['headline'] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( (string) $def['headline'] ); ?>">
								<textarea class="large-text" rows="2" name="<?php echo esc_attr( $name ); ?>[sub]" placeholder="<?php echo esc_attr( (string) $def['sub'] ); ?>"><?php echo esc_textarea( (string) ( $row['sub'] ?? '' ) ); ?></textarea>
							</td>
							<td style="width:5%"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[disabled]" value="1" <?php checked( ! empty( $row['disabled'] ) ); ?>></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
