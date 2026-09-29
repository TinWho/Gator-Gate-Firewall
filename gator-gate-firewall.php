/*
 * Code Snippet:       Gator-Gate-Firewall for bbPress
 * Description:        Advanced per-forum content filtration, clipboard copy-paste baggage reduction.
 * Version:            0.1.4-Alpha
 * AUTHOR:             Tin Who (https://tinfoilwho.com)
 * License:            GPL-2.0-or-later
 *
 * AI-generated/AI-assisted code provided AS-IS.
 * User assumes all risk and responsibility for use.
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/*
============================================================================
SECTION 1: BACKEND FORUM ATTRIBUTES UI
============================================================================
*/

add_action( 'add_meta_boxes', 'gator_gate_register_metabox' );
add_action( 'save_post_forum', 'gator_gate_save_metabox_data', 10, 2 );


function gator_gate_register_metabox() {

    add_meta_box(
        'gator_gate_firewall_ports',
        'Gator Gate Firewall Ports',
        'gator_gate_render_metabox_content',
        'forum',
        'side',
        'high'
    );
}


function gator_gate_render_metabox_content( $post ) {

    wp_nonce_field(
        'gator_gate_secure_save_action',
        'gator_gate_secure_save_field'
    );


    /*
    ============================================================================
    GATOR-GATE PORT DEFINITIONS
    ============================================================================
    */

    $gator_gate_ports = [

        'port_1' => 'PORT 1: HEADINGS WITH ALIGNMENT & LISTS ALLOWED',
        'port_2' => 'PORT 2: IMAGES ALLOWED',
        'port_3' => 'PORT 3: VIDEOS ALLOWED',
        'port_4' => 'PORT 4: TABLES ALLOWED',
        'port_5' => 'PORT 5: COLOURS ALLOWED',
        'port_6' => 'PORT 6: OTHER ALIGNMENT',
        'port_7' => 'PORT 7: FONTS & SIZES ALLOWED',
        'port_8' => 'PORT 8: PRESERVE PREFORMATTED TEXT',
        'port_9' => 'PORT 9: BYPASS FIREWALL (DISABLE ALL RULES)',

    ];


    echo '<p style="font-size:12px; color:#646970; margin-bottom:12px;">';
    echo 'Toggle specific isolated firewall ports for this forum:';
    echo '</p>';


    /*
    ============================================================================
    CHECK WHETHER FIREWALL CONFIGURATION HAS EVER BEEN SAVED
    ============================================================================
    */

    $gator_gate_configuration_saved = get_post_meta(
        $post->ID,
        '_gator_gate_configuration_saved',
        true
    );


    /*
    ============================================================================
    PORT CHECKBOXES
    ============================================================================
    */

    echo '<div id="gator_gate_ui_wrapper">';

    foreach ( $gator_gate_ports as $key_id => $box_label ) {

        $is_checked = get_post_meta(
            $post->ID,
            '_gator_gate_' . $key_id,
            true
        );


        /*
        ========================================================================
        DEFAULT STATE FOR UNSAVED CONFIGURATION
        ========================================================================
        *
        * If this forum has never had a Gator-Gate configuration saved,
        * Port 9 is displayed as active by default.
        *
        */

        if (
            ! $gator_gate_configuration_saved
            && 'port_9' === $key_id
        ) {

            $is_checked = 1;
        }


        echo '<p style="margin:8px 0; line-height:1.5;">';

        echo '<label style="display:inline-flex; align-items:center; width:100%; cursor:pointer; font-weight:500;">';

        echo '<input type="checkbox" '
            . 'name="gator_gate_' . esc_attr( $key_id ) . '" '
            . 'value="1" '
            . checked( 1, $is_checked, false )
            . ' style="margin:0 10px 0 0;" />';

        echo '<span>'
            . esc_html( $box_label )
            . '</span>';

        echo '</label>';

        echo '</p>';
    }

    echo '</div>';


    /*
    ============================================================================
    PER-FORUM MAXIMUM SUBMITTED CONTENT SIZE
    ============================================================================
    *
    * Default: 0 characters = unlimited.
    */

    $gator_gate_default_limit = 0;

    $gator_gate_submission_limit = get_post_meta(
        $post->ID,
        '_gator_gate_submission_limit',
        true
    );


    /*
    * Empty meta means the forum has not yet been given a limit.
    */

    if ( '' === $gator_gate_submission_limit ) {

        $gator_gate_submission_limit =
            $gator_gate_default_limit;
    }


    echo '<div style="margin-top:15px; padding-top:12px; border-top:1px solid #dcdcde;">';


    /*
    ============================================================================
    COMPACT CHARACTER-LIMIT CONTROL
    ============================================================================
    */

    echo '<div style="display:flex; align-items:center; gap:7px;">';

    echo '<input type="number" '
        . 'name="gator_gate_submission_limit" '
        . 'value="' . esc_attr( $gator_gate_submission_limit ) . '" '
        . 'min="0" '
        . 'step="100" '
        . 'style="width:85px;" />';

    echo '<span style="font-size:14px;">characters</span>';

    echo '</div>';


    /*
    ============================================================================
    DESCRIPTION BELOW CONTROL
    ============================================================================
    */

    echo '<p style="font-size:13px; color:#646970; margin:7px 0 0;">';
    echo 'Maximum content size after Gator-Gate cleanup. 0 = unlimited.';
    echo '</p>';

    echo '</div>';


    /*
    ============================================================================
    ISOLATED SAVE BUTTON
    ============================================================================
    */

    echo '<div style="margin-top:15px; padding-top:12px; border-top:1px solid #dcdcde; text-align:right;">';

    echo '<button type="submit" '
        . 'name="gator_gate_isolated_save" '
        . 'value="1" '
        . 'class="button button-secondary button-large" '
        . 'style="width:100%; text-align:center; background:#135e96; color:#fff; border-color:#135e96; font-weight:600;">';

    echo 'Update Firewall Configuration Only';

    echo '</button>';

    echo '<p style="font-size:11px; color:#646970; margin:6px 0 0; text-align:center;">';
    echo 'Updates ports and submission limit without modifying post content.';
    echo '</p>';

    echo '</div>';
}


/*
============================================================================
SECTION 2: SAVE FIREWALL CONFIGURATION
============================================================================
*/

function gator_gate_save_metabox_data( $post_id, $post ) {

    /*
    ============================================================================
    ONLY RUN WHEN OUR DEDICATED FIREWALL BUTTON WAS CLICKED
    ============================================================================
    */

    if (
        ! isset( $_POST['gator_gate_isolated_save'] )
        || '1' !== $_POST['gator_gate_isolated_save']
    ) {
        return $post_id;
    }


    /*
    ============================================================================
    NONCE VALIDATION
    ============================================================================
    */

    if (
        ! isset( $_POST['gator_gate_secure_save_field'] )
        || ! wp_verify_nonce(
            $_POST['gator_gate_secure_save_field'],
            'gator_gate_secure_save_action'
        )
    ) {
        return $post_id;
    }


    /*
    ============================================================================
    AUTOSAVE PROTECTION
    ============================================================================
    */

    if (
        defined( 'DOING_AUTOSAVE' )
        && DOING_AUTOSAVE
    ) {
        return $post_id;
    }


    /*
    ============================================================================
    REVISION PROTECTION
    ============================================================================
    */

    if ( wp_is_post_revision( $post_id ) ) {
        return $post_id;
    }


    /*
    ============================================================================
    PERMISSION CHECK
    ============================================================================
    */

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return $post_id;
    }


    /*
    ============================================================================
    SAVE PORT SETTINGS
    ============================================================================
    */

    $gator_gate_keys = [

        'port_1',
        'port_2',
        'port_3',
        'port_4',
        'port_5',
        'port_6',
        'port_7',
        'port_8',
        'port_9',

    ];


    foreach ( $gator_gate_keys as $key_id ) {

        $post_var_name = 'gator_gate_' . $key_id;


        if (
            isset( $_POST[ $post_var_name ] )
            && '1' === $_POST[ $post_var_name ]
        ) {

            update_post_meta(
                $post_id,
                '_gator_gate_' . $key_id,
                1
            );

        } else {

            delete_post_meta(
                $post_id,
                '_gator_gate_' . $key_id
            );
        }
    }


    /*
    ============================================================================
    SAVE PER-FORUM MAXIMUM SUBMITTED CONTENT SIZE
    ============================================================================
    *
    * 0 = unlimited.
    */

    if ( isset( $_POST['gator_gate_submission_limit'] ) ) {

        $submission_limit = absint(
            $_POST['gator_gate_submission_limit']
        );


        /*
        * Minimum: 0.
        *
        * 0 means unlimited.
        */

        $submission_limit = max(
            0,
            $submission_limit
        );


        update_post_meta(
            $post_id,
            '_gator_gate_submission_limit',
            $submission_limit
        );
    }


    /*
    ============================================================================
    MARK CONFIGURATION AS EXPLICITLY SAVED
    ============================================================================
    *
    * Once this marker exists, the administrator's individual port settings
    * become authoritative.
    *
    * Until this marker exists:
    *
    * Port 9 = bypass
    * Submission limit = 0 / unlimited
    *
    */

    update_post_meta(
        $post_id,
        '_gator_gate_configuration_saved',
        1
    );


    return $post_id;
}



/*
============================================================================
SECTION 3: CONTENT FILTER HOOKS
============================================================================
*/

add_filter(
    'bbp_new_reply_pre_content',
    'gator_gate_run_contextual_scrubber',
    9999,
    1
);

add_filter(
    'bbp_new_topic_pre_content',
    'gator_gate_run_contextual_scrubber',
    9999,
    1
);

add_filter(
    'bbp_edit_reply_pre_content',
    'gator_gate_run_contextual_scrubber',
    9999,
    1
);

add_filter(
    'bbp_edit_topic_pre_content',
    'gator_gate_run_contextual_scrubber',
    9999,
    1
);



function gator_gate_run_contextual_scrubber( $incoming_payload ) {

    $forum_id = 0;


    /*
    ============================================================================
    DETECT TARGET FORUM
    ============================================================================
    */

    if (
        function_exists( 'bbp_get_reply_forum_id' )
        && bbp_is_reply_edit()
    ) {

        $forum_id = bbp_get_reply_forum_id(
            bbp_get_reply_id()
        );

    } elseif (
        function_exists( 'bbp_get_topic_forum_id' )
        && bbp_is_topic_edit()
    ) {

        $forum_id = bbp_get_topic_forum_id(
            bbp_get_topic_id()
        );

    } elseif (
        isset( $_POST['bbp_forum_id'] )
    ) {

        $forum_id = absint(
            $_POST['bbp_forum_id']
        );

    } elseif (
        isset( $_POST['bbp_topic_id'] )
    ) {

        $topic_id = absint(
            $_POST['bbp_topic_id']
        );

        if ( function_exists( 'bbp_get_topic_forum_id' ) ) {

            $forum_id = bbp_get_topic_forum_id(
                $topic_id
            );
        }
    }



    /*
    ============================================================================
    CHECK WHETHER FIREWALL CONFIGURATION HAS EVER BEEN SAVED
    ============================================================================
    */

    $gator_gate_configuration_saved = false;

    if ( $forum_id > 0 ) {

        $gator_gate_configuration_saved = get_post_meta(
            $forum_id,
            '_gator_gate_configuration_saved',
            true
        );
    }



    /*
    ============================================================================
    LOAD ACTIVE PORT SETTINGS
    ============================================================================
    */

    $active_ports = [];

    if ( $forum_id > 0 ) {

        for ( $i = 1; $i <= 9; $i++ ) {

            $active_ports[ 'port_' . $i ] = get_post_meta(
                $forum_id,
                '_gator_gate_port_' . $i,
                true
            );
        }
    }



    /*
    ============================================================================
    PORT 9: BYPASS FORMATTING FIREWALL
    ============================================================================
    *
    * Port 9 bypasses Gator-Gate formatting rules.
    *
    * DEFAULT:
    * If this forum has never had a Gator-Gate configuration saved,
    * Port 9 is automatically treated as active.
    *
    * The submission-size safeguard remains independent.
    *
    */

    $is_bypass_active = false;

    if ( $forum_id > 0 ) {

        /*
        * No configuration has ever been saved.
        *
        * Default to bypass mode.
        */

        if ( ! $gator_gate_configuration_saved ) {

            $is_bypass_active = true;

        } else {

            $is_bypass_active = get_post_meta(
                $forum_id,
                '_gator_gate_port_9',
                true
            );
        }
    }



    /*
    ============================================================================
    INITIAL CHARACTER COUNT
    ============================================================================
    */

    $count_pre_firewall = mb_strlen(
        $incoming_payload,
        'UTF-8'
    );



    /*
    ============================================================================
    MAIN FIREWALL
    ============================================================================
    */

    if ( ! $is_bypass_active ) {


        /*
        ========================================================================
        ATTRIBUTE BLACKLIST
        ========================================================================
        */

        $attribute_blacklist = [

            'data-src',
            'data-scr',
            'data-sizes',
            'data-srcset',
            'sizes',
            'srcset',
            'onclick',
            'onload',

        ];


        /*
        ========================================================================
        DECODE HTML ENTITIES
        ========================================================================
        */

        $incoming_payload = html_entity_decode(
            $incoming_payload,
            ENT_QUOTES,
            'UTF-8'
        );


        /*
        ========================================================================
        NORMALISE WHITESPACE INSIDE HTML TAGS
        ========================================================================
        */

        $incoming_payload = preg_replace_callback(
            '/<[^>]+>/s',
            function( $tag_match ) {

                return preg_replace(
                    '/\s+/',
                    ' ',
                    $tag_match[0]
                );
            },
            $incoming_payload
        );


        /*
        ========================================================================
        BUILD ATTRIBUTE PATTERN
        ========================================================================
        */

        $attr_pattern = implode(
            '|',
            array_map(
                'preg_quote',
                $attribute_blacklist
            )
        );


        /*
        ========================================================================
        REMOVE QUOTED ATTRIBUTES
        ========================================================================
        */

        $incoming_payload = preg_replace(
            '/\s+(?:'
            . $attr_pattern
            . ')\s*=\s*(?:"[^"]*"|\'[^\']*\')/is',
            '',
            $incoming_payload
        );


        /*
        ========================================================================
        REMOVE SMART-QUOTED ATTRIBUTES
        ========================================================================
        */

        $incoming_payload = preg_replace(
            '/\s+(?:'
            . $attr_pattern
            . ')\s*=\s*(?:[”’][^”’]*[”’])/ius',
            '',
            $incoming_payload
        );


        /*
        ========================================================================
        REMOVE UNQUOTED ATTRIBUTES
        ========================================================================
        */

        $incoming_payload = preg_replace(
            '/\s+(?:'
            . $attr_pattern
            . ')\s*=\s*[^[:space:]>]+/i',
            '',
            $incoming_payload
        );



        /*
        ========================================================================
        MASTER WHITELIST - port controls can apply additional filtering and granular removal
        ========================================================================
        */

        $master_whitelist = [

            '<p>',
            '<br>',
            '<a>',
            '<span>',
            '<em>',
            '<strong>',

            '<h1>',
            '<h2>',
            '<h3>',
            '<h4>',
            '<h5>',
            '<h6>',

            '<ul>',
            '<ol>',
            '<li>',

            '<img>',
            '<picture>',
            '<source>',
            '<iframe>',
            

            '<table>',
            '<thead>',
            '<tbody>',
            '<tr>',
            '<th>',
            '<td>',

            '<pre>',
            '<code>',

        ];


        /*
        ========================================================================
        CONVERT SUMMARY DIVS BEFORE STRIP_TAGS
        ========================================================================
        */

        $incoming_payload = preg_replace(
            '/<div([^>]*?)class="[^"]*summary[^"]*"[^>]*>(.*?)<\/div>/is',
            '<p>$2</p>',
            $incoming_payload
        );


        /*
        ========================================================================
        APPLY BASELINE WHITELIST
        ========================================================================
        */

        $incoming_payload = strip_tags(
            $incoming_payload,
            implode(
                '',
                $master_whitelist
            )
        );



        /*
        ========================================================================
        PORT 1: HEADINGS WITH ALIGNMENT & LISTS
        ========================================================================
        */

        if ( empty( $active_ports['port_1'] ) ) {

            $incoming_payload = preg_replace(
                '/<\/?(h[1-6]|ul|ol|li)[^>]*>/i',
                '',
                $incoming_payload
            );
        }



        /*
        ========================================================================
        PORT 2: IMAGES
        ========================================================================
        */

        if ( empty( $active_ports['port_2'] ) ) {

            $incoming_payload = preg_replace(
                '/<(img|picture|source)[^>]*>|<\/(picture|source)>/i',
                '',
                $incoming_payload
            );

        } else {

            $incoming_payload = preg_replace(
                '/\s*(srcset|data-srcset|sizes|data-sizes|media)="[^"]*"/i',
                '',
                $incoming_payload
            );

            $incoming_payload = preg_replace(
                '/<\/?(picture|source)[^>]*>/i',
                '',
                $incoming_payload
            );
        }



 // ========================================================================
// PORT 3: VIDEOS & VIDEO PROVIDERS
// ========================================================================

$gator_gate_video_whitelist = array(
    'youtube.com',
    'youtu.be',
    'youtube-nocookie.com',
    'vimeo.com',
);

if ( empty( $active_ports['port_3'] ) ) {

    // --------------------------------------------------------------------
    // PORT 3 OFF
    // Remove ALL iframes.
    // Remove ALL embed shortcodes.
    // --------------------------------------------------------------------

    $incoming_payload = preg_replace(
        '/<iframe\b[^>]*>.*?<\/iframe>/is',
        '',
        $incoming_payload
    );

    $incoming_payload = preg_replace(
        '/\[embed\b[^\]]*\].*?\[\/embed\]/is',
        '',
        $incoming_payload
    );

} else {

    // --------------------------------------------------------------------
    // PORT 3 ON
    // Only allow iframes and embeds from approved video providers.
    // --------------------------------------------------------------------

    // Check iframe src against the video whitelist.
    $incoming_payload = preg_replace_callback(
        '/<iframe\b[^>]*\bsrc\s*=\s*([\'"])(.*?)\1[^>]*>.*?<\/iframe>/is',
        function ( $match ) use ( $gator_gate_video_whitelist ) {

            $src = trim( $match[2] );

            foreach ( $gator_gate_video_whitelist as $video_domain ) {

                if ( preg_match(
                    '~^(?:https?:)?//(?:www\.)?' .
                    preg_quote( $video_domain, '~' ) .
                    '(/|$)~i',
                    $src
                ) ) {
                    return $match[0];
                }
            }

            // Not an approved video provider.
            return '';
        },
        $incoming_payload
    );

    // Check [embed] URL against the same video whitelist.
    $incoming_payload = preg_replace_callback(
        '/\[embed\b[^\]]*\]\s*(.*?)\s*\[\/embed\]/is',
        function ( $match ) use ( $gator_gate_video_whitelist ) {

            $url = trim( $match[1] );

            foreach ( $gator_gate_video_whitelist as $video_domain ) {

                if ( preg_match(
                    '~^(?:https?:)?//(?:www\.)?' .
                    preg_quote( $video_domain, '~' ) .
                    '(/|$)~i',
                    $url
                ) ) {
                    // Normalise protocol-relative URLs.
                    if ( strpos( $url, '//' ) === 0 ) {
                        $url = 'https:' . $url;
                    }

                    return preg_replace(
                        '/(\[embed\b[^\]]*\]).*?(\[\/embed\])/is',
                        '$1' . $url . '$2',
                        $match[0]
                    );
                }
            }

            // Not an approved video provider.
            return '';
        },
        $incoming_payload
    );
}
              /*
        ========================================================================
        PORT 4: TABLES
        ========================================================================
        */

        if ( empty( $active_ports['port_4'] ) ) {

            $incoming_payload = preg_replace(
                '/<\/?(table|thead|tbody|tr|th|td)[^>]*>/i',
                '',
                $incoming_payload
            );

        } else {

            $incoming_payload = preg_replace(
                '/<(table|thead|tbody|tr|th|td)[^>]*>/i',
                '<$1>',
                $incoming_payload
            );
        }



        /*
        ========================================================================
        PORT 5: COLOURS
        ========================================================================
        */

        if ( empty( $active_ports['port_5'] ) ) {

            $incoming_payload = preg_replace(
                '/\bcolor\s*:\s*[^;"]+;?/i',
                '',
                $incoming_payload
            );

            $incoming_payload = preg_replace(
                '/\bbackground-color\s*:\s*[^;"]+;?/i',
                '',
                $incoming_payload
            );
        }



        /*
        ========================================================================
        PORT 6: TEXT ALIGNMENT
        ========================================================================
        */

        if ( empty( $active_ports['port_6'] ) ) {

            $incoming_payload = preg_replace(
                '/\btext-align\s*:\s*[^;"]+;?/i',
                '',
                $incoming_payload
            );
        }



        /*
        ========================================================================
        PORT 7: FONTS & SIZES
        ========================================================================
        */

        if ( empty( $active_ports['port_7'] ) ) {

            $incoming_payload = preg_replace(
                '/\bfont-family\s*:\s*[^;"]+;?/i',
                '',
                $incoming_payload
            );

            $incoming_payload = preg_replace(
                '/\bfont-size\s*:\s*[^;"]+;?/i',
                '',
                $incoming_payload
            );

            $incoming_payload = preg_replace(
                '/\bfont-weight\s*:\s*[^;"]+;?/i',
                '',
                $incoming_payload
            );

            $incoming_payload = preg_replace(
                '/\bfont-style\s*:\s*[^;"]+;?/i',
                '',
                $incoming_payload
            );
        }



        /*
        ========================================================================
        PORT 8: PREFORMATTED TEXT
        ========================================================================
        */

        if ( empty( $active_ports['port_8'] ) ) {

            $incoming_payload = preg_replace(
                '/<\/?(pre|code)[^>]*>/i',
                '',
                $incoming_payload
            );

        } else {

            $incoming_payload = preg_replace(
                '/<(pre|code)[^>]*>/i',
                '<$1>',
                $incoming_payload
            );
        }



        /*
        ========================================================================
        FINAL EMPTY STYLE CLEANUP
        ========================================================================
        */

        $incoming_payload = preg_replace(
            '/\s+style=["\']\s*;?\s*["\']/i',
            '',
            $incoming_payload
        );
    }



    /*
    ============================================================================
    FINAL CHARACTER COUNT
    ============================================================================
    */

    $count_post_firewall = mb_strlen(
        $incoming_payload,
        'UTF-8'
    );


    $vaporised_bytes =
        $count_pre_firewall
        - $count_post_firewall;



    /*
    ============================================================================
    PER-FORUM MAXIMUM SUBMITTED CONTENT SIZE
    ============================================================================
    *
    * Default: 0 = unlimited.
    *
    * Port 9 bypasses formatting rules but the submission-size safeguard
    * remains independently active when a non-zero limit is configured.
    *
    */

    $gator_gate_default_limit = 0;

    $gator_gate_submission_limit = '';


    if ( $forum_id > 0 ) {

        $gator_gate_submission_limit = get_post_meta(
            $forum_id,
            '_gator_gate_submission_limit',
            true
        );
    }


    /*
    ============================================================================
    DEFAULT LIMIT
    ============================================================================
    *
    * Empty means no explicit forum limit has been saved.
    *
    * 0 = unlimited.
    *
    */

    if ( '' === $gator_gate_submission_limit ) {

        $gator_gate_submission_limit =
            $gator_gate_default_limit;
    }


    $gator_gate_submission_limit = absint(
        $gator_gate_submission_limit
    );



    /*
    ============================================================================
    FINAL SIZE CHECK
    ============================================================================
    *
    * A limit of 0 means unlimited.
    *
    */

    $gator_gate_final_size = mb_strlen(
        $incoming_payload,
        'UTF-8'
    );


    if (
        $gator_gate_submission_limit > 0
        && $gator_gate_final_size > $gator_gate_submission_limit
    ) {

        wp_die(

            '<h1>Gator-Gate Submission Limit</h1>'

            . '<p>Your submission is too large for this forum.</p>'

            . '<p><strong>Maximum:</strong> '
            . esc_html(
                number_format_i18n(
                    $gator_gate_submission_limit
                )
            )
            . ' characters</p>'

            . '<p><strong>Your cleaned submission:</strong> '
            . esc_html(
                number_format_i18n(
                    $gator_gate_final_size
                )
            )
            . ' characters</p>'

            . '<p>Please remove some content and try again.</p>',

            'Gator-Gate Submission Limit',

            [
                'response'  => 413,
                'back_link' => true,
            ]
        );
    }



    /*
    ============================================================================
    DEBUG AUDIT TRACE
    ============================================================================
    */

    $incoming_payload .=

        "\n\n<!-- GATOR_GATE FIREWALL "
        . "[Forum:ID {$forum_id}]: "
        . "[Before: {$count_pre_firewall} chars] "
        . "-> [After: {$count_post_firewall} chars]. "
        . "Purged {$vaporised_bytes} formatting bytes. "
        . "-->";



    /*
    ============================================================================
    RETURN CLEANED CONTENT
    ============================================================================
    */

    return $incoming_payload;
}


// END GATOR-GATE
