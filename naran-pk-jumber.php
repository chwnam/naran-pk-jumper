<?php
/**
 * Plugin Name: Naran Primary Key Jumper
 * Description: Jump your primary key next value of WordPress tables.
 * Author:      changwoo
 * Author URI:  https://blog.changwoo.pe.kr
 * Plugin URI:  https://github.com/chwnam/naran-pk-jumper
 * Version:     1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_admin() ) {
	return;
}

const NPJ_VERSION = '1.0.1';


add_action( 'admin_enqueue_scripts', 'npj_enqueue_scripts', 100 );
function npj_enqueue_scripts( string $hook ) {
	if ( 'tools_page_npj' === $hook ) {
		wp_enqueue_script(
			'npj-script',
			plugins_url( 'script.js', __FILE__ ),
			[ 'jquery' ],
			NPJ_VERSION
		);

		wp_enqueue_style(
			'npj-style',
			plugins_url( 'style.css', __FILE__ ),
			[],
			NPJ_VERSION
		);
	}
}

add_action( 'admin_menu', 'npj_add_admin_menu' );
function npj_add_admin_menu() {
	add_submenu_page(
		'tools.php',
		'PK Jumper',
		'PK Jumper',
		'administrator',
		'npj',
		'npj_output_admin_menu'
	);
}

function npj_output_admin_menu() {
	$tables = npj_get_tables();
	$pk     = [];
	$ai     = [];

	foreach ( $tables as $table ) {
		$key = npj_get_pk( $table );
		if ( $key ) {
			$pk[ $table ] = $key;
		}

		$next = npj_get_next_ai( $table );
		if ( $next > 0 ) {
			$ai[ $table ] = $next;
		}
	}
	?>
    <div class="wrap">
        <h1 class="wp-heading-inline">PK Jumper</h1>
        <hr class="wp-header-end">
        <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
            <table class="wp-list-table striped fixed widefat npj-table">
                <thead>
                <tr>
                    <td class="col-checkbox">&nbsp;</td>
                    <td class="col-table_name">Table</td>
                    <td class="col-pk">P.K.</td>
                    <td class="col-ai_next">A.I. Next</td>
                    <td class="col-ai_jump">A.I. Jump</td>
                </tr>
                </thead>
                <tbody>
				<?php foreach ( $tables as $table ) : ?>
                    <tr class="<?php echo npj_is_builtin_table( $table ) ? 'npj-builtin' : ''; ?>">
                        <td class="col-checkbox">
							<?php if ( isset( $pk[ $table ] ) && isset( $ai[ $table ] ) ) : ?>
                                <input id="chk-<?php echo esc_attr( $table ); ?>"
                                       class="npj-checkbox"
                                       rel="ai-<?php echo esc_attr( $table ); ?>"
                                       type="checkbox"
                                       value="yes">
							<?php endif; ?>
                        </td>
                        <td class="col-table_name <?php npj_td_class( $table ); ?>">
                            <label for="chk-<?php echo esc_attr( $table ); ?>"><?php echo esc_html( $table ); ?></label>
                        </td>
                        <td class="col-pk">
							<?php echo esc_html( $pk[ $table ] ?? '' ); ?>
                        </td>
                        <td class="col-ai_next">
                            <span>
                                <?php echo esc_html( isset( $ai[ $table ] ) ? number_format( $ai[ $table ] ) : '' ); ?>
                            </span>
                        </td>
                        <td class="col-ai_jump">
							<?php if ( isset( $pk[ $table ] ) && isset( $ai[ $table ] ) ) : ?>
                                <input id="ai-<?php echo esc_attr( $table ); ?>"
                                       type="number"
                                       name="npj[<?php echo esc_attr( $table ); ?>]"
                                       class="text large-text ai_jump"
                                       value=""
                                       min="<?php echo isset( $ai[ $table ] ) ? $ai[ $table ] + 1 : ''; ?>"
                                       disabled="disabled">
                                <label for="ai-<?php echo esc_attr( $table ); ?>"
                                       class="screen-reader-text">A.I. Jmp</label>
							<?php endif; ?>
                        </td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <input id="show-only-builtin"
                       type="checkbox">
                <label for="show-only-builtin">Show only WordPress built-in tables.</label>
            </p>
            <input type="hidden" name="action" value="npj_jump">
			<?php
			submit_button( 'Apply' );
			wp_nonce_field( 'npj', '_npj_nonce' )
			?>
        </form>
    </div>
	<?php
}


function npj_get_tables(): array {
	global $wpdb;

	$result = $wpdb->get_col( 'SHOW TABLES' );

	if ( $result ) {
		$result = array_values( $result );
		sort( $result );
	} else {
		$result = [];
	}

	return $result;
}


function npj_get_pk( string $table ): string {
	global $wpdb;

	$result = $wpdb->get_row( "SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'" );

	if ( $result && isset( $result->Column_name ) ) {
		return $result->Column_name;
	}

	return '';
}


function npj_get_next_ai( string $table ): int {
	global $wpdb;

	$result = $wpdb->get_row( $wpdb->prepare( "SHOW TABLE STATUS LIKE %s", $table ) );

	if ( $result && isset( $result->Auto_increment ) ) {
		return $result->Auto_increment;
	}

	return - 1;
}


function npj_set_next_ai( string $table, int $jump ) {
	global $wpdb;

	$current_jump = npj_get_next_ai( $table );

	if ( $jump > 0 && $jump > $current_jump ) {
		$wpdb->query( $wpdb->prepare( "ALTER TABLE `{$table}` AUTO_INCREMENT = %d", $jump ) );
	}
}


function npj_is_builtin_table( string $table ): bool {
	global $wpdb;

	$tables = [
		$wpdb->commentmeta,
		$wpdb->comments,
		$wpdb->links,
		$wpdb->options,
		$wpdb->postmeta,
		$wpdb->posts,
		$wpdb->termmeta,
		$wpdb->terms,
		$wpdb->term_relationships,
		$wpdb->term_taxonomy,
		$wpdb->usermeta,
		$wpdb->users
	];

	return in_array( $table, $tables, true );
}


function npj_td_class( string $table ) {
	if ( npj_is_builtin_table( $table ) ) {
		echo 'npj-wp-builtin-table';
	}
}


add_action( 'admin_post_npj_jump', 'npj_jump' );
function npj_jump() {
	check_admin_referer( 'npj', '_npj_nonce' );

	if ( current_user_can( 'administrator' ) && isset( $_POST['npj'] ) && is_array( $_POST['npj'] ) ) {
		$npj = $_POST['npj'];

		foreach ( $npj as $table => $jump ) {
			$jump = absint( $jump );
			if ( $jump ) {
				npj_set_next_ai( $table, $jump );
			}
		}

		wp_safe_redirect( wp_get_referer() );
		exit;
	}
}