<?php
/**
 * Ladattavat oppaat.
 *
 *   Hallinta:  WordPress → Oppaat (otsikko, lyhyt kuvaus, kansikuva, PDF)
 *   Sivulle:   [nordic_oppaat]            kaikki oppaat korttiruudukkona
 *              [nordic_oppaat ids="12,15"] valitut oppaat
 *              [nordic_oppaat aihe="ikkunat"] tai aihe="ovet"  valmiit Skaala-esitteet aiheittain
 *              [nordic_oppaat tyyli="lista"] kompakti lista (esim. ponnahdusikkunaan)
 *              [nordic_opas id="12"]      yksi opas leveänä nostona (esim. artikkelin loppuun)
 *   Linkki muotoa /sivu/#nordic-opas-12 avaa kyseisen oppaan latauslomakkeen.
 *
 * Lataus kysyy etunimen ja sähköpostin. Kontakti viedään FluentCRM:ään
 * (tagit "Lähde: Opas" ja "Opas: <oppaan nimi>"), opas lähetetään sähköpostiin
 * ja latauslinkki näytetään heti sivulla. Jatkoviestit lähtevät vain
 * markkinointiluvan antaneille, kuten muissakin Nordic CRM -lomakkeissa.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NORDIC_CRM_GUIDE_TYPE', 'nordic_opas' );

add_action( 'init', function () {
	register_post_type( NORDIC_CRM_GUIDE_TYPE, array(
		'labels'          => array(
			'name'               => 'Oppaat',
			'singular_name'      => 'Opas',
			'add_new'            => 'Lisää opas',
			'add_new_item'       => 'Lisää uusi opas',
			'edit_item'          => 'Muokkaa opasta',
			'new_item'           => 'Uusi opas',
			'all_items'          => 'Kaikki oppaat',
			'search_items'       => 'Hae oppaita',
			'not_found'          => 'Oppaita ei löytynyt',
			'not_found_in_trash' => 'Roskakorissa ei ole oppaita',
			'featured_image'     => 'Kansikuva',
			'set_featured_image' => 'Valitse kansikuva',
			'menu_name'          => 'Oppaat',
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'menu_position'   => 26,
		'menu_icon'       => 'dashicons-book-alt',
		'supports'        => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
		'capability_type' => 'page',
		'map_meta_cap'    => true,
	) );
} );

/* -------------------------------------------------------------------------
 * Hallinta: PDF-kenttä ja ohjeet
 * ---------------------------------------------------------------------- */

add_action( 'add_meta_boxes_' . NORDIC_CRM_GUIDE_TYPE, function () {
	add_meta_box( 'nordic-opas-pdf', 'Oppaan tiedosto (PDF)', 'nordic_crm_guide_metabox', NORDIC_CRM_GUIDE_TYPE, 'normal', 'high' );
} );

function nordic_crm_guide_metabox( $post ) {
	wp_nonce_field( 'nordic_opas_save', 'nordic_opas_nonce' );
	wp_enqueue_media();
	$pdf   = (string) get_post_meta( $post->ID, '_nordic_opas_pdf', true );
	$badge = (string) get_post_meta( $post->ID, '_nordic_opas_badge', true );
	?>
	<p>
		<input type="url" class="large-text" id="nordic-opas-pdf" name="nordic_opas_pdf" value="<?php echo esc_attr( $pdf ); ?>" placeholder="https://…/opas.pdf">
	</p>
	<p>
		<button type="button" class="button" id="nordic-opas-pick">Valitse tai lataa PDF</button>
		<?php if ( $pdf ) : ?>
			<a class="button-link" href="<?php echo esc_url( $pdf ); ?>" target="_blank" rel="noopener" style="margin-left:8px;">Avaa nykyinen</a>
		<?php endif; ?>
	</p>
	<p>
		<label for="nordic-opas-badge"><strong>Merkki kortin kulmassa</strong></label><br>
		<input type="text" id="nordic-opas-badge" name="nordic_opas_badge" value="<?php echo esc_attr( $badge ); ?>" placeholder="Ilmainen opas">
		<span class="description">Esim. <code>Ilmainen opas</code> tai <code>Tuote-esite</code>.</span>
	</p>
	<p class="description">
		<strong>Lyhyt kuvaus</strong> (Ote-kenttä) näkyy kortissa oppaan nimen alla, esim. "12 kohdan muistilista ennen tarjouspyyntöä".
		<strong>Kansikuva</strong> näkyy kortin yläosassa (pystykuva, esim. PDF:n ensimmäinen sivu).
		<strong>Järjestys</strong> (Sivun määritteet) ratkaisee oppaiden järjestyksen.
	</p>
	<p class="description">Sivulle: <code>[nordic_oppaat]</code> näyttää kaikki oppaat, <code>[nordic_opas id="<?php echo (int) $post->ID; ?>"]</code> vain tämän.</p>
	<script>
	(function () {
		var btn = document.getElementById('nordic-opas-pick'), input = document.getElementById('nordic-opas-pdf'), frame;
		if (!btn || !window.wp || !wp.media) return;
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			if (!frame) {
				frame = wp.media({ title: 'Valitse oppaan PDF', button: { text: 'Käytä tätä' }, library: { type: 'application/pdf' }, multiple: false });
				frame.on('select', function () { input.value = frame.state().get('selection').first().toJSON().url; });
			}
			frame.open();
		});
	})();
	</script>
	<?php
}

add_action( 'save_post_' . NORDIC_CRM_GUIDE_TYPE, function ( $post_id ) {
	if ( ! isset( $_POST['nordic_opas_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nordic_opas_nonce'] ) ), 'nordic_opas_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$pdf = isset( $_POST['nordic_opas_pdf'] ) ? esc_url_raw( trim( wp_unslash( $_POST['nordic_opas_pdf'] ) ) ) : '';
	update_post_meta( $post_id, '_nordic_opas_pdf', $pdf );
	$badge = isset( $_POST['nordic_opas_badge'] ) ? sanitize_text_field( wp_unslash( $_POST['nordic_opas_badge'] ) ) : '';
	update_post_meta( $post_id, '_nordic_opas_badge', $badge );
} );

add_filter( 'manage_' . NORDIC_CRM_GUIDE_TYPE . '_posts_columns', function ( $cols ) {
	return array(
		'cb'           => $cols['cb'],
		'title'        => 'Opas',
		'nordic_short' => 'Lyhytkoodi',
		'nordic_pdf'   => 'PDF',
		'date'         => $cols['date'],
	);
} );

add_action( 'manage_' . NORDIC_CRM_GUIDE_TYPE . '_posts_custom_column', function ( $col, $post_id ) {
	if ( 'nordic_short' === $col ) {
		echo '<code>[nordic_opas id="' . (int) $post_id . '"]</code>';
	} elseif ( 'nordic_pdf' === $col ) {
		$pdf = get_post_meta( $post_id, '_nordic_opas_pdf', true );
		echo $pdf ? '<a href="' . esc_url( $pdf ) . '" target="_blank" rel="noopener">Avaa</a>' : '<span style="color:#b32d2e;">Puuttuu</span>';
	}
}, 10, 2 );

/* -------------------------------------------------------------------------
 * Apufunktiot
 * ---------------------------------------------------------------------- */

/** Julkaistu opas, jolla on PDF, tai null. */
function nordic_crm_guide( $id ) {
	$post = get_post( (int) $id );
	if ( ! $post || NORDIC_CRM_GUIDE_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
		return null;
	}
	$pdf = (string) get_post_meta( $post->ID, '_nordic_opas_pdf', true );
	if ( ! $pdf ) {
		return null;
	}
	return array(
		'id'    => (int) $post->ID,
		'title' => get_the_title( $post ),
		'desc'  => has_excerpt( $post ) ? get_the_excerpt( $post ) : '',
		'pdf'   => $pdf,
		'cover' => get_the_post_thumbnail_url( $post, 'medium_large' ),
		'badge' => (string) get_post_meta( $post->ID, '_nordic_opas_badge', true ),
	);
}

/** Allekirjoitus, jolla latauslinkki näytetään vain lomakkeen lähettäneelle. */
function nordic_crm_guide_key( $id ) {
	return substr( hash_hmac( 'sha256', 'nordic-opas|' . (int) $id, wp_salt( 'nonce' ) ), 0, 16 );
}

/** Tagi "Opas: <nimi>" (luodaan tarvittaessa). */
function nordic_crm_guide_tag_id( $title ) {
	if ( ! class_exists( '\FluentCrm\App\Models\Tag' ) ) {
		return 0;
	}
	$name = 'Opas: ' . $title;
	$tag  = \FluentCrm\App\Models\Tag::where( 'title', $name )->first();
	if ( ! $tag ) {
		$tag = \FluentCrm\App\Models\Tag::create( array( 'title' => $name, 'slug' => sanitize_title( $name ) ) );
	}
	return (int) $tag->id;
}

/* -------------------------------------------------------------------------
 * Lyhytkoodit
 * ---------------------------------------------------------------------- */

/** Valmiiden oppaiden ID:t aiheittain (ikkunat / ovet). */
function nordic_crm_guide_ids_for_topic( $topic ) {
	$groups = array(
		'ikkunat' => array( 'skaala-aukea-ikkuna', 'skaala-aasa-ikkuna', 'skaala-aava-ikkuna' ),
		'ovet'    => array( 'skaala-terassi-ja-parvekeovet', 'skaala-palo-ovet' ),
	);
	$topic = sanitize_key( $topic );
	if ( ! isset( $groups[ $topic ] ) ) {
		return array();
	}
	$done = (array) get_option( 'nordic_crm_default_guides', array() );
	$ids  = array();
	foreach ( $groups[ $topic ] as $slug ) {
		if ( ! empty( $done[ $slug ] ) ) {
			$ids[] = (int) $done[ $slug ];
		}
	}
	return $ids;
}

add_shortcode( 'nordic_oppaat', function ( $atts ) {
	$atts  = shortcode_atts( array( 'ids' => '', 'otsikko' => '', 'aihe' => '', 'tyyli' => '' ), $atts );
	$query = array(
		'post_type'      => NORDIC_CRM_GUIDE_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => 24,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'fields'         => 'ids',
	);
	$ids = array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) );
	if ( ! $ids && $atts['aihe'] ) {
		$ids = nordic_crm_guide_ids_for_topic( $atts['aihe'] );
		if ( ! $ids ) {
			return '';
		}
	}
	if ( $ids ) {
		$query['post__in'] = $ids;
		$query['orderby']  = 'post__in';
	}
	$guides = array_filter( array_map( 'nordic_crm_guide', get_posts( $query ) ) );
	if ( ! $guides ) {
		return current_user_can( 'edit_posts' ) ? '<p><em>Oppaita ei ole vielä julkaistu. Lisää ne kohdassa Oppaat → Lisää opas. (Tämä huomautus näkyy vain ylläpitäjille.)</em></p>' : '';
	}
	$out = nordic_crm_guides_assets() . '<div class="nordic-guides' . ( 'lista' === $atts['tyyli'] ? ' is-list' : '' ) . '">';
	if ( $atts['otsikko'] ) {
		$out .= '<h2 class="ng-heading">' . esc_html( $atts['otsikko'] ) . '</h2>';
	}
	$out .= '<div class="ng-grid">';
	foreach ( $guides as $g ) {
		$out .= nordic_crm_guide_card( $g, false );
	}
	return $out . '</div></div>';
} );

add_shortcode( 'nordic_opas', function ( $atts ) {
	$atts = shortcode_atts( array( 'id' => 0 ), $atts );
	$g    = nordic_crm_guide( $atts['id'] );
	if ( ! $g ) {
		return current_user_can( 'edit_posts' ) ? '<p><em>Opasta ' . (int) $atts['id'] . ' ei löydy tai siltä puuttuu PDF. (Näkyy vain ylläpitäjille.)</em></p>' : '';
	}
	return nordic_crm_guides_assets() . '<div class="nordic-guides is-single">' . nordic_crm_guide_card( $g, true ) . '</div>';
} );

/**
 * Yksi opaskortti lomakkeineen.
 */
function nordic_crm_guide_card( $g, $wide ) {
	$s       = nordic_crm_settings();
	$privacy = $s['privacy_url'] ? $s['privacy_url'] : get_privacy_policy_url();
	$anchor  = 'nordic-opas-' . $g['id'];
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$done  = isset( $_GET['nc'], $_GET['nc_opas'], $_GET['nc_k'] ) && 'opas' === $_GET['nc'] && (int) $_GET['nc_opas'] === $g['id']
		&& hash_equals( nordic_crm_guide_key( $g['id'] ), sanitize_text_field( wp_unslash( $_GET['nc_k'] ) ) );
	$error = isset( $_GET['nc_err'], $_GET['nc_opas'] ) && 'opas' === $_GET['nc_err'] && (int) $_GET['nc_opas'] === $g['id'];
	// phpcs:enable
	$time = time();

	$out  = '<article class="ng-card' . ( $wide ? ' is-wide' : '' ) . '" id="' . esc_attr( $anchor ) . '">';
	$out .= '<div class="ng-cover">' . ( $g['cover']
		? '<img src="' . esc_url( $g['cover'] ) . '" alt="" loading="lazy">'
		: '<span class="ng-cover-ph" aria-hidden="true"><svg viewBox="0 0 24 24" width="42" height="42" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4.5A2.5 2.5 0 016.5 2H20v17H6.5A2.5 2.5 0 004 21.5z"/><path d="M4 21.5V4.5"/><path d="M8 7h8M8 11h6"/></svg></span>' )
		. '<span class="ng-badge">' . esc_html( $g['badge'] ? $g['badge'] : 'Ilmainen opas' ) . '</span></div>';
	$out .= '<div class="ng-body">';
	$out .= '<h3 class="ng-title">' . esc_html( $g['title'] ) . '</h3>';
	if ( $g['desc'] ) {
		$out .= '<p class="ng-desc">' . esc_html( $g['desc'] ) . '</p>';
	}

	if ( $done ) {
		$out .= '<div class="ng-done" role="status"><p><strong>Kiitos.</strong> Lähetimme oppaan myös sähköpostiisi, jotta se on tallessa.</p>'
			. '<a class="ng-btn" href="' . esc_url( $g['pdf'] ) . '" target="_blank" rel="noopener" data-nordic-guide-open="' . esc_attr( $g['id'] ) . '">Avaa opas (PDF)</a></div>';
		$out .= '<script>window.dataLayer=window.dataLayer||[];window.dataLayer.push({event:"guide_download",guide_id:' . (int) $g['id'] . ',guide_name:' . wp_json_encode( $g['title'] ) . '});</script>';
		return $out . '</div></article>';
	}

	$out .= '<details class="ng-details"' . ( $error || $wide ? ' open' : '' ) . '>';
	$out .= '<summary class="ng-btn">Lataa opas</summary>';
	$out .= '<form class="ng-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	if ( $error ) {
		$out .= '<p class="ng-error" role="alert">Tarkista sähköpostiosoite ja yritä uudelleen.</p>';
	}
	$out .= '<input type="hidden" name="action" value="nordic_crm_form">';
	$out .= '<input type="hidden" name="nc_type" value="opas">';
	$out .= '<input type="hidden" name="nc_opas" value="' . esc_attr( $g['id'] ) . '">';
	$out .= '<input type="hidden" name="nc_t" value="' . esc_attr( $time ) . '">';
	$out .= '<input type="hidden" name="nc_h" value="' . esc_attr( nordic_crm_form_token( $time ) ) . '">';
	$out .= '<input type="hidden" name="nc_back" value="' . esc_attr( get_permalink() ) . '">';
	$out .= '<div class="ncf-hp" aria-hidden="true"><label>Verkkosivu <input type="text" name="nc_website" tabindex="-1" autocomplete="off"></label></div>';
	$out .= '<label class="ng-field"><span>Etunimi</span><input type="text" name="nc_first_name" autocomplete="given-name"></label>';
	$out .= '<label class="ng-field"><span>Sähköposti</span><input type="email" name="nc_email" required autocomplete="email"></label>';
	$out .= '<label class="ng-consent"><input type="checkbox" name="nc_consent" value="1"> <span>Saa lähettää minulle myös muutaman vinkin ikkuna- ja oviremontista. Voin perua tilauksen milloin tahansa.</span></label>';
	$out .= '<button type="submit" class="ng-btn ng-submit">Lähetä opas sähköpostiini</button>';
	if ( $privacy ) {
		$out .= '<p class="ng-privacy">Saat oppaan heti tällä sivulla ja sähköpostiisi. <a href="' . esc_url( $privacy ) . '">Tietosuojaseloste</a></p>';
	}
	$out .= '</form></details>';
	return $out . '</div></article>';
}

/**
 * Lomakkeen käsittely (kutsutaan nordic_crm_handle_form-funktiosta).
 *
 * @return array|null [lead, guide] tai null, jos opasta ei ole.
 */
function nordic_crm_guide_from_post() {
	$id = isset( $_POST['nc_opas'] ) ? absint( $_POST['nc_opas'] ) : 0; // phpcs:ignore
	return $id ? nordic_crm_guide( $id ) : null;
}

/* -------------------------------------------------------------------------
 * Tyylit (tulostetaan kerran sivulla)
 * ---------------------------------------------------------------------- */

function nordic_crm_guides_assets() {
	static $printed = false;
	if ( $printed ) {
		return '';
	}
	$printed = true;
	$css = '.nordic-guides{margin:32px 0}'
		. '.nordic-guides .ng-heading{margin:0 0 20px}'
		. '.nordic-guides .ng-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:22px;align-items:start}'
		. '.nordic-guides .ng-card{display:flex;flex-direction:column;background:#fff;border:1px solid rgba(22,48,59,.1);border-radius:20px;overflow:hidden;box-shadow:0 1px 2px rgba(16,37,48,.05),0 6px 18px rgba(16,37,48,.06);transition:transform .3s cubic-bezier(.22,1,.36,1),box-shadow .3s}'
		. '.nordic-guides .ng-card:hover{transform:translateY(-3px);box-shadow:0 18px 40px rgba(16,37,48,.12)}'
		. '.nordic-guides .ng-cover{position:relative;aspect-ratio:4/3;background:linear-gradient(135deg,#16303B,#1D5E79);color:#FFD866;display:flex;align-items:center;justify-content:center;overflow:hidden}'
		. '.nordic-guides .ng-cover img{width:100%;height:100%;object-fit:cover;object-position:top;display:block}'
		. '.nordic-guides .ng-badge{position:absolute;right:14px;top:14px;padding:5px 11px;border-radius:999px;background:#FFD866;color:#16303B;font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase}'
		. '.nordic-guides .ng-body{padding:20px 22px 22px;display:flex;flex-direction:column;gap:8px;color:#3D5561}'
		. '.nordic-guides .ng-title{margin:0;font-size:1.2rem;line-height:1.25;color:#16303B}'
		. '.nordic-guides .ng-desc{margin:0 0 6px}'
		. '.nordic-guides .ng-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:48px;padding:0 22px;border:0;border-radius:999px;background:#FFD866;color:#16303B!important;font:inherit;font-weight:700;font-size:15.5px;text-decoration:none!important;cursor:pointer;list-style:none;transition:transform .2s}'
		. '.nordic-guides .ng-btn:hover{transform:translateY(-1px)}'
		. '.nordic-guides summary.ng-btn::-webkit-details-marker{display:none}'
		. '.nordic-guides summary.ng-btn::after{content:"\2193";font-weight:700}'
		. '.nordic-guides .ng-details[open] summary.ng-btn{display:none}'
		. '.nordic-guides .ng-form{display:grid;gap:12px;margin-top:4px}'
		. '.nordic-guides .ng-field{display:flex;flex-direction:column;gap:5px;font-weight:600;font-size:14px;color:#16303B}'
		. '.nordic-guides .ng-field input{min-height:48px;padding:11px 14px;font:inherit;font-size:16px;font-weight:400;color:#16303B;background:#F6F8F9;border:1.5px solid transparent;border-radius:12px;box-shadow:inset 0 0 0 1px rgba(22,48,59,.1)}'
		. '.nordic-guides .ng-field input:focus{outline:none;background:#fff;border-color:#1D5E79;box-shadow:0 0 0 4px rgba(29,94,121,.15)}'
		. '.nordic-guides .ng-consent{display:flex;gap:10px;align-items:flex-start;font-size:13.5px;line-height:1.5;cursor:pointer}'
		. '.nordic-guides .ng-consent input{margin-top:3px;width:18px;height:18px;flex:none;accent-color:#1D5E79}'
		. '.nordic-guides .ng-submit{width:100%}'
		. '.nordic-guides .ng-privacy{margin:0;font-size:12.5px;color:#647A84}'
		. '.nordic-guides .ng-privacy a{color:#1D5E79}'
		. '.nordic-guides .ng-error{margin:0;color:#B3361F;font-weight:500}'
		. '.nordic-guides .ng-done{display:grid;gap:12px;padding:16px;border-radius:14px;background:rgba(47,125,91,.1);color:#16303B}'
		. '.nordic-guides .ng-done p{margin:0}'
		. '.nordic-guides .ncf-hp{position:absolute!important;left:-9999px;width:1px;height:1px;overflow:hidden}'
		. '.nordic-guides.is-single .ng-card.is-wide{flex-direction:row}'
		. '.nordic-guides.is-single .ng-card.is-wide .ng-cover{flex:0 0 38%;aspect-ratio:auto;min-height:260px}'
		. '.nordic-guides.is-single .ng-card.is-wide .ng-body{flex:1;padding:26px 28px}'
		. '@media(max-width:700px){.nordic-guides.is-single .ng-card.is-wide{flex-direction:column}.nordic-guides.is-single .ng-card.is-wide .ng-cover{flex:none;aspect-ratio:4/3;min-height:0}}'
		. '.nordic-guides.is-list{margin:0}'
		. '.nordic-guides.is-list .ng-grid{grid-template-columns:1fr;gap:10px}'
		. '.nordic-guides.is-list .ng-card{flex-direction:row;align-items:flex-start;border-radius:16px;box-shadow:none}'
		. '.nordic-guides.is-list .ng-card:hover{transform:none;box-shadow:0 6px 18px rgba(16,37,48,.08)}'
		. '.nordic-guides.is-list .ng-cover{flex:0 0 78px;aspect-ratio:3/4;margin:12px 0 12px 12px;border-radius:8px;border:1px solid rgba(22,48,59,.1)}'
		. '.nordic-guides.is-list .ng-badge{display:none}'
		. '.nordic-guides.is-list .ng-body{flex:1;min-width:0;padding:12px 14px 14px;gap:4px}'
		. '.nordic-guides.is-list .ng-title{font-size:1rem}'
		. '.nordic-guides.is-list .ng-desc{font-size:13.5px;line-height:1.45;margin:0 0 4px}'
		. '.nordic-guides.is-list .ng-btn{min-height:40px;padding:0 16px;font-size:14px;align-self:flex-start}'
		. '.nordic-guides.is-list .ng-submit{align-self:stretch}'
		. '.nordic-guides.is-list .ng-done{padding:12px}'
		. '@media(prefers-reduced-motion:reduce){.nordic-guides *{transition:none!important}}';
	$js = '(function(){function h(){var m=location.hash.match(/^#nordic-opas-(\\d+)$/);if(!m)return;var c=document.getElementById("nordic-opas-"+m[1]);var d=c&&c.querySelector("details");if(d&&!d.open){d.open=true;var i=d.querySelector("input[name=nc_first_name]");if(i)setTimeout(function(){i.focus({preventScroll:true})},300)}}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",h);else h();addEventListener("hashchange",h)})();'
		. 'document.addEventListener("click",function(e){var a=e.target.closest&&e.target.closest("[data-nordic-guide-open]");if(a&&window.dataLayer)window.dataLayer.push({event:"guide_open",guide_id:+a.getAttribute("data-nordic-guide-open")});});';
	return '<style>' . $css . '</style><script>' . $js . '</script>';
}

/* -------------------------------------------------------------------------
 * Valmiit oppaat (Skaalan tuote-esitteet)
 *
 * Asennus kopioi lisäosan mukana tulevat PDF:t ja kansikuvat mediakirjastoon
 * ja luo niistä oppaat. Kukin luodaan vain kerran: jos opas poistetaan,
 * sitä ei palauteta päivityksessä.
 * ---------------------------------------------------------------------- */

function nordic_crm_default_guides() {
	return array(
		'skaala-aukea-ikkuna'           => array(
			'title' => 'Aukea-ikkuna – sisäänaukeava',
			'desc'  => 'Suomen yleisin ikkunamalli: Aukea, Aukea+ ja Mökki-ikkuna. U-arvot, värit ja karmisyvyydet.',
		),
		'skaala-aasa-ikkuna'            => array(
			'title' => 'Aasa-ikkuna – ulosaukeava',
			'desc'  => 'Skandinavian suosituin malli: avaus yhdellä painikkeella, U-arvo jopa 0,8 ja lapsilukko vakiona.',
		),
		'skaala-aava-ikkuna'            => array(
			'title' => 'Aava-ikkuna – kiinteä',
			'desc'  => 'Markkinoiden siroimmat kiinteät ikkunat, jopa noin 8 m². Saatavana myös EI30-paloikkunana.',
		),
		'skaala-terassi-ja-parvekeovet' => array(
			'title' => 'Terassi- ja parvekeovet',
			'desc'  => 'Kolmilasiset terassi- ja parvekeovet HDF- tai alumiinipinnalla. Mallit Aamu, Aero, Loiste ja Louna.',
		),
		'skaala-palo-ovet'              => array(
			'title' => 'Palo-ovet',
			'desc'  => 'Milloin palo-ovi tarvitaan, mitä EI30 tarkoittaa ja missä koossa Skaalan palo-ovet tehdään.',
		),
	);
}

/**
 * @return string[] Luotujen oppaiden nimet.
 */
function nordic_crm_install_default_guides() {
	$done    = (array) get_option( 'nordic_crm_default_guides', array() );
	$created = array();
	$order   = 1;
	require_once ABSPATH . 'wp-admin/includes/image.php';
	foreach ( nordic_crm_default_guides() as $slug => $g ) {
		$order++;
		if ( isset( $done[ $slug ] ) ) {
			continue;
		}
		$pdf_id   = nordic_crm_import_asset( 'assets/oppaat/' . $slug . '.pdf', $g['title'] );
		$cover_id = nordic_crm_import_asset( 'assets/oppaat/' . $slug . '-kansi.jpg', $g['title'] . ' – kansi' );
		if ( ! $pdf_id ) {
			continue;
		}
		$post_id = wp_insert_post( array(
			'post_type'    => NORDIC_CRM_GUIDE_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $g['title'],
			'post_excerpt' => $g['desc'],
			'menu_order'   => $order,
		) );
		if ( ! $post_id || is_wp_error( $post_id ) ) {
			continue;
		}
		update_post_meta( $post_id, '_nordic_opas_pdf', wp_get_attachment_url( $pdf_id ) );
		update_post_meta( $post_id, '_nordic_opas_badge', 'Tuote-esite' );
		if ( $cover_id ) {
			set_post_thumbnail( $post_id, $cover_id );
		}
		$done[ $slug ] = (int) $post_id;
		$created[]     = $g['title'];
	}
	update_option( 'nordic_crm_default_guides', $done, false );
	return $created;
}

/** Kopioi lisäosan tiedoston mediakirjastoon. Palauttaa liitteen ID:n tai 0. */
function nordic_crm_import_asset( $rel, $title ) {
	$src = NORDIC_CRM_DIR . '/' . $rel;
	if ( ! is_readable( $src ) ) {
		return 0;
	}
	$upload = wp_upload_bits( basename( $src ), null, (string) file_get_contents( $src ) );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$type = wp_check_filetype( $upload['file'] );
	$id   = wp_insert_attachment( array(
		'post_mime_type' => $type['type'],
		'post_title'     => $title,
		'post_status'    => 'inherit',
	), $upload['file'] );
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	if ( 0 === strpos( (string) $type['type'], 'image/' ) ) {
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
	}
	return (int) $id;
}
