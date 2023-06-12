<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all active zoho lenders post ID .
 *
 * @return array
 */
function m118_get_current_lenders_post_ids() {
    $active_lender_records = get_option( 'm118_zoho_current_lender_post_records' );
    return ( $active_lender_records ) ? array_keys( $active_lender_records ) : array();
}

/**
 * Return current lenders data .
 *
 * @param  array $args 
 * @return array
 */
function m118_get_lenders( $args = array() ) {
    $defaults = array(
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order'   => 'ASC',
    );
     
    $args = wp_parse_args( $args, $defaults );

    $args['post_type'] = 'lenders';
    $args['post__in'] = m118_get_current_lenders_post_ids();

    $posts = get_posts( $args );
    if ( $posts ) :
        return $posts;
    endif;
    
    return false;
}

/**
 * Return current lender data .
 *
 * @param  int $post_id 
 * @return array
 */
function m118_get_lender( $post_id = null ) {
    global $post;
    if( !$post_id ) {
        if( $post ) $post_id = $post->ID;
    }

    if( $post_id ) {
        $lender = get_post( $post_id );
        if( $lender ) return $lender;
    }
    
    return false;
}

/**
 * Return current lender image url .
 *
 * @param  int|object $post_id 
 * @param  string $img_type 
 * @param  int $attachment_id 
 * @return string
 */
function m118_get_lender_image_url( $post_id, $img_type = 'full', $attachment_id = null ) {
    $image_id = get_post_thumbnail_id( $post_id );
    // Now fetch from attachment first
    $image_ids = m118_get_lender_metadata( $post_id, 'zoho_attachments_ids' );
    if( isset( $image_ids[0] ) ) {
        $image_id = $image_ids[0];
    }

    if( $attachment_id ) {
        $image_id = $attachment_id;
    }

    $image = wp_get_attachment_image_src( $image_id, $img_type );
    
    return ( isset( $image[0] ) ) ? $image[0] : false;
}

/**
 * Return current lender meta data .
 *
 * @param  int $post_id 
 * @return array
 */
function m118_get_lender_metadata( $post_id, $meta_key, $sub_key = '' ) {
    $meta_key = ltrim( $meta_key, '_' );

    if( $sub_key ) {
        $meta_key = '__' . $meta_key . '_' . $sub_key;
    }else{
        $meta_key = '_' . $meta_key;
    }

    if( $post_id ) {
        $metadata = get_post_meta( $post_id, $meta_key, true );
        return $metadata;
    }
    
    return false;
}

/**
 * Return product term meta data .
 *
 * @param  int $term_id 
 * @return array
 */
function m118_get_product_term_metadata( $term_id, $meta_key, $sub_key = '' ) {
    $meta_key = ltrim( $meta_key, '_' );

    if( $sub_key ) {
        $meta_key = '__' . $meta_key . '_' . $sub_key;
    }else{
        $meta_key = '_' . $meta_key;
    }

    if( $term_id ) {
        $metadata = get_term_meta( $term_id, $meta_key, true );
        return $metadata;
    }
    
    return false;
}

/**
 * Return current lender products data .
 *
 * @param  array $args 
 * @return array
 */
function m118_get_post_lender_products( $args = array() ) {
    $defaults = array(
        'orderby'                => 'name',
        'order'                  => 'ASC',
        'hide_empty'             => false,
    );
    $args = wp_parse_args( $args, $defaults );

    $args['taxonomy'] = 'lender_product';

    $query = new WP_Term_Query($args);

    return ( $query->get_terms() ) ? $query->get_terms() : array();
}

/**
 * Return current lender property types data .
 *
 * @param  array $args 
 * @return array
 */
function m118_get_post_lender_property_types( $args = array() ) {
    $defaults = array(
        'orderby'                => 'name',
        'order'                  => 'ASC',
        'hide_empty'             => false,
    );
    $args = wp_parse_args( $args, $defaults );

    $args['taxonomy'] = 'property_type';

    $query = new WP_Term_Query($args);

    return ( $query->get_terms() ) ? $query->get_terms() : array();
}

/**
 * Return lenders with alphabetically data .
 *
 * @param  array $args 
 * @return array
 */
function m118_get_alphabetically_lenders( $args = array() ) {
    
    $transient_key = 'm118_get_alphabetically_lenders';
    //check first args
    if( isset( $args['tax_query'] ) && $args['tax_query'] ) {
        $initial_taxdata = $args['tax_query'][0];
        $term = ( isset( $initial_taxdata['terms'] ) && !is_array( $initial_taxdata['terms'] ) ) ? $initial_taxdata['terms'] : '';
        $transient_key = 'm118_get_alphabetically_lenders' . '_tax_'. $initial_taxdata['taxonomy'] . '_' . $term;
    }elseif( isset( $args['meta_query'] ) && $args['meta_query'] ) {
        $initial_metadata = $args['meta_query'][0];
        $value = ( isset( $initial_metadata['value'] ) && !is_array( $initial_metadata['value'] ) ) ? $initial_metadata['value'] : '';
        $transient_key = 'm118_get_alphabetically_lenders' . '_meta_'. $initial_metadata['key'] . '_' . $value;
    }else{
        $transient_key = 'm118_get_alphabetically_lenders';
    }

    if( get_transient( $transient_key ) ) {
        $alphabetically_lenders =  maybe_unserialize( get_transient( $transient_key ) );
    } else {
        // get all lenders first
        $lenders = m118_get_lenders( $args );

        $alphabet_array = range('a', 'z');
        $alphabet_array = array_flip($alphabet_array);
        $alphabetically_lenders = array();
        if( $lenders ) {
            foreach( $lenders as $lender ) {
                $title = $lender->post_title;
                $title_start_with = strtolower( $title[0] );
                
                if( array_key_exists( $title_start_with, $alphabet_array ) ) {
                    $alphabetically_lenders[$title_start_with][] = $lender;
                }else{
                    $alphabetically_lenders['hash'][] = $lender;
                }
            }
        }
        set_transient( $transient_key, maybe_serialize( $alphabetically_lenders ), DAY_IN_SECONDS );
    
    }
    return $alphabetically_lenders;
}

function m118_get_niche_products( $niche_type = '', $loan_type = '' ) {
    $meta_query = array();
	if( $niche_type ) {
        $meta_query[] = array (
            'key' => '_niche_type',
            'value' => serialize(strval($niche_type)), 
            'compare' => 'LIKE',
        );
    }

    if( $loan_type ) {
        $meta_query[] = array (
            'key' => '_loan_type_m',
            'value' => serialize(strval($loan_type)), 
            'compare' => 'LIKE',
        );
    }

	$top5_products = m118_get_post_lender_products(
		array(
			'orderby'       => 'meta_value_num',
			'number'        => 5,
			'meta_key'      => '_initial_rate_apr',
			'meta_query' => $meta_query,
		)
	);
	return $top5_products;
}

function m118_get_niche_faqs( $property_type = 'HMO', $lending_type = '' ) {
    $meta_query = array();
	if( $property_type ) {
        $meta_query[] = array (
            'key' => '_property_type',
            'value' => serialize(strval($property_type)), 
            'compare' => 'LIKE',
        );
    }

    if( $lending_type ) {
        $meta_query[] = array (
            'key' => '_loan_type',
            'value' => serialize(strval($lending_type)), 
            'compare' => 'LIKE',
        );
    }

	$args = array(
        'post_type' => 'faqs',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => $meta_query,
    );

    $faqs = get_posts( $args );

    // for updated wp queries the above LIKE compare with serialize data may not work properly
    // So, to do the filter process looping through filter checking
    
    if( $faqs ) {
        foreach ($faqs as $key => $faq) {
            if( $property_type ) {
                $search_type = trim( $property_type );
				$search_type = strtolower( $search_type );
				$niche_types = get_post_meta( $faq->ID, '_property_type', true );
				if( $niche_types ) {
					$niche_types_lower = array_map( 'strtolower', $niche_types );
				
					if( !in_array( $search_type, $niche_types_lower ) ) {
						if( isset( $faqs[$key] ) ) unset( $faqs[$key] );
					}
				}
            }
        
            if( $lending_type ) {
                $search_type = trim( $lending_type );
				$search_type = strtolower( $search_type );
				$loan_types = get_post_meta( $faq->ID, '_loan_type', true );
				if( $loan_types ) {
					$loan_types_lower = array_map( 'strtolower', $loan_types );
				
					if( !in_array( $search_type, $loan_types_lower ) ) {
						if( isset( $faqs[$key] ) ) unset( $faqs[$key] );
					}
				}
            }
        }
    }
   
	return $faqs;
}