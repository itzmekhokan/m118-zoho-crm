<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * M118_Zoho_Data class.
 */
class M118_Zoho_Data {

    /**
     * Constructor.
     */
    public function __construct() {
        
    }

    /**
	 * Set zoho record id.
	 *
	 * @param string $post_id
	 */
	public static function set_record_id( $post_id, $record_id ) {
		update_post_meta( $post_id, '_zoho_record_id', $record_id );
	}

    /**
	 * Get zoho record id.
	 *
	 * @param string $post_id
	 */
	public static function get_record_id( $post_id ) {
		return get_post_meta( $post_id, '_zoho_record_id', true );
	}

    /**
	 * Sync Zoho to WP
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $data
	 */
	public static function syncZohoToWP( $zohodata ) {
        if( !$zohodata ) return;
        $current_records = array();
        foreach ( $zohodata as $key => $data ) {
            $post_id = null;
            // check for existing post
            $existing_post_id = null;
            if( isset( $data['id'] ) ) {
                $existing_post_id = self::get_post_id_by( '_zoho_record_id', $data['id'] );
            }
            
            if( $existing_post_id ) {
                $post_id = $existing_post_id;
                // do update post
                self::do_update( $existing_post_id, $data );
            } else {
                // do create post
                $post_id = self::do_create( $data );
            }

            $current_records[$post_id] = $data['id'];
            // add zoho data sync log
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Data > syncZohoToWP sync done for index- '.$key.' and record - '.$data['id'] );
        }
        // update option with active zoho lenders records
        update_option( 'm118_zoho_current_lender_post_records', $current_records );

        // delete old transients
        self::delete_prefix_transients( 'm118_get_alphabetically_lenders' );
    }

    /**
	 * Sync Zoho FAQ to WP
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $data
	 */
	public static function syncZohoFAQsToWP( $zohodata ) {
        if( !$zohodata ) return;
        $for_module = 'FAQs';
        
        foreach ( $zohodata as $key => $data ) {
            $post_id = null;
            // check for existing post
            $existing_post_id = null;
            if( isset( $data['id'] ) ) {
                $existing_post_id = self::get_post_id_by( '_zoho_record_id', $data['id'] );
            }
            
            if( $existing_post_id ) {
                $post_id = $existing_post_id;
                // do update post
                self::do_update( $existing_post_id, $data, $for_module );
            } else {
                // do create post
                $post_id = self::do_create( $data, $for_module );
            }

            // add zoho data sync log
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Data > syncZohoFAQsToWP sync done for index- '.$key.' and record - '.$data['id'] );
        }
    }

    /**
	 * Sync Zoho Fees to WP
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $data
	 */
	public static function syncZohoFeesToWP( $zohodata ) {
        if( !$zohodata ) return;
        $for_module = 'Fees';
        
        foreach ( $zohodata as $key => $data ) {
            $post_id = null;
            // check for existing post
            $existing_post_id = null;
            if( isset( $data['id'] ) ) {
                $existing_post_id = self::get_post_id_by( '_zoho_record_id', $data['id'] );
            }
            
            if( $existing_post_id ) {
                $post_id = $existing_post_id;
                // do update post
                self::do_update( $existing_post_id, $data, $for_module );
            } else {
                // do create post
                $post_id = self::do_create( $data, $for_module );
            }

            // add zoho data sync log
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Data > syncZohoFeesToWP sync done for index- '.$key.' and record - '.$data['id'] );
        }
    }

    /**
	 * Sync single Zoho record to WP post
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 * @param string $data
	 */
	public static function sync_post_from_zoho( $zohodata ) {
        if( !$zohodata ) return;
        foreach ( $zohodata as $key => $data ) {
            $post_id = null;
            // check for existing post
            $existing_post_id = null;
            if( isset( $data['id'] ) ) {
                $existing_post_id = self::get_post_id_by( '_zoho_record_id', $data['id'] );
            }
            
            if( $existing_post_id ) {
                $post_id = $existing_post_id;
                // do update post
                self::do_update( $existing_post_id, $data );
            } else {
                // do create post
                $post_id = self::do_create( $data );
            }
            // add zoho data sync log
            doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Data > sync_post_from_zoho sync done for record - '.$data['id'] );
        }
    }

    /**
	 * Update post data
	 * @param int $post_id
	 * @param array $data
     * @param string $for_module
	 * @return int|bool
	 */
	public static function do_update( $post_id, $data = array(), $for_module = 'Vendors' ) {

        $post_data = self::get_module_post_data( $for_module, $data );

		// do updates
		$post = array(
            'ID' => $post_id,
			'post_content'   => $post_data['post_content'],
			'post_excerpt'   => isset( $post_data['post_excerpt'] ) ? $post_data['post_excerpt'] : '',
			'post_title'     => $post_data['post_title'],
			'post_name'		 => $post_data['post_name'],
            'post_modified'  => isset( $post_data['post_modified'] ) ? $post_data['post_modified'] : '',
            'post_author'    => isset( $post_data['post_author'] ) ? $post_data['post_author'] : get_current_user_id(),
		);
        // remove post parent if already set and there is no $post_data parent
        $post_parent = wp_get_post_parent_id( $post_id );
        if( $post_parent ) {
            $post['post_parent'] = isset( $post_data['post_parent'] ) ? $post_data['post_parent'] : 0;
        }

		wp_update_post( $post );

        if( $post_id ) {
            if( in_array( $for_module, array( 'Vendors' ) ) ) {
                // update attachments
                self::do_update_attachments( $post_id, $data );
                // update taxonomy
                self::do_update_taxonomy( $post_id, $data );
            }
            
            // update meta data
            self::do_update_meta( $post_id, $data, $for_module );
        }

        // add zoho data sync log
        doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Data > do_update sync done for record - '.$data['id'] );
	}

    /**
	 * create post data
	 * @param array $data
     * @param string $for_module
	 * @return int|bool
	 */
	public static function do_create( $data = array(), $for_module = 'Vendors' ) {

        $post_data = self::get_module_post_data( $for_module, $data );
        
        switch ( $for_module ) {
            case 'Vendors':
                $post = array(
                    'post_type'      => 'lenders',
                    'post_status'    => 'publish',
                );
                $post = wp_parse_args( $post_data, $post );
                break;

            case 'FAQs':
                $post = array(
                    'post_type'      => 'faqs',
                    'post_status'    => 'publish',
                );
                $post = wp_parse_args( $post_data, $post );
                break;

            case 'Fees':
                $post = array(
                    'post_type'      => 'fees',
                    'post_status'    => 'publish',
                );
                $post = wp_parse_args( $post_data, $post );
                break;
            
            default:
                $post = array(
                    'post_type'      => 'lenders',
                    'post_status'    => 'publish',
                );
                $post = wp_parse_args( $post_data, $post );
                break;
        }

        $post_id = wp_insert_post( $post );
        
        if( $post_id ) {
            self::set_record_id( $post_id, $data['id'] );
            // update meta data
            self::do_update_meta( $post_id, $data, $for_module );

            if( in_array( $for_module, array( 'Vendors' ) ) ) {
                // update attachments
                self::do_update_attachments( $post_id, $data );
                // update taxonomy
                self::do_update_taxonomy( $post_id, $data );
            }
            
        }

        // add zoho data sync log
        doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Data > do_create sync done for record - '.$data['id'] );
        // for further use
        return $post_id;
	}

    /**
	 * Get Zoho module post data for insert or update
	 * @param string $for_module
	 * @param array $data
	 * @return int|bool
	 */
	public static function get_module_post_data( $for_module, $data = array() ) {
        $post_data = array();
        switch ( $for_module ) {
            case 'Vendors':
                $post_slug = sanitize_title_with_dashes( $data['Vendor_Name'] );
                if( isset( $data['slug'] ) && $data['slug'] ) 
                    $post_slug = $data['slug'];

                $author_id = get_m118_zoho_settings('default_zoho2wp_user');
                if( isset( $data['Owner']['email'] ) ) {
                    $user = get_user_by( 'email', $data['Owner']['email'] );
                    if( $user && isset( $user->ID ) ) $author_id = $user->ID;
                }
                $post_data['post_author'] = $author_id;
                $post_data['post_name'] = $post_slug;
                $post_data['post_title'] = $data['Vendor_Name'];
                $post_data['post_content'] = $data['About'];
                $post_data['post_excerpt'] = $data['Lender_Meta'];
                $post_data['post_date']      = gmdate( 'Y-m-d H:i:s', strtotime( $data['Created_Time'] ) );
                $post_data['post_modified']  = gmdate( 'Y-m-d H:i:s', strtotime( $data['Modified_Time'] ) );
                break;

            case 'FAQs':
                $title_trim = substr( $data['Name'], 0, 60 ); // default standard WordPress post length for title
                $post_slug = sanitize_title_with_dashes( $title_trim );

                $author_id = get_m118_zoho_settings('default_zoho2wp_user');
                if( isset( $data['Owner']['email'] ) ) {
                    $user = get_user_by( 'email', $data['Owner']['email'] );
                    if( $user && isset( $user->ID ) ) $author_id = $user->ID;
                }
                $content = ( isset( $data['Answer_2'] ) && $data['Answer_2'] ) ? $data['Answer_2'] : $data['Answer'];
                $post_data['post_author'] = $author_id;
                $post_data['post_name'] = $post_slug;
                $post_data['post_title'] = $title_trim;
                $post_data['post_content'] = $content;
                $post_data['post_excerpt'] = $data['Name'];
                // Update FAQs linked module as post parent 
                if( isset( $data['FAQs'] ) && $data['FAQs'] ) {
                    $linked_module_record_id = ( isset( $data['FAQs']['id'] ) && $data['FAQs']['id'] ) ? $data['FAQs']['id'] : 0;
                    if( $linked_module_record_id ) {
                        // check FAQs linked module ID exist in WP post
                        $linked_post_id = self::get_post_id_by( '_zoho_record_id', $linked_module_record_id );
                        if( $linked_post_id ) {
                            $post_data['post_parent'] = $linked_post_id;
                        }
                    }
                }
                break;

            case 'Fees':
                $title_trim = substr( $data['Name'], 0, 60 ); // default standard WordPress post length for title
                $post_slug = sanitize_title_with_dashes( $title_trim );

                $author_id = get_m118_zoho_settings('default_zoho2wp_user');
                if( isset( $data['Owner']['email'] ) ) {
                    $user = get_user_by( 'email', $data['Owner']['email'] );
                    if( $user && isset( $user->ID ) ) $author_id = $user->ID;
                }
                $content = ( isset( $data['Fee_Explanation'] ) && $data['Fee_Explanation'] ) ? $data['Fee_Explanation'] : '';
                $post_data['post_author'] = $author_id;
                $post_data['post_name'] = $post_slug;
                $post_data['post_title'] = $title_trim;
                $post_data['post_content'] = $content;
                
                break;
            
            default:
                # code...
                break;
        }

        return $post_data;
    }

    /**
	 * Update post meta data
	 * @param int $post_id
	 * @param array $data
     * @param string $for_module
	 * @return int|bool
	 */
	public static function do_update_meta( $post_id, $data = array(), $for_module = 'Vendors' ) {
		foreach( $data as $key => $value ) {
            $key = str_replace( '$', '', $key );
			$key = '_' . strtolower( $key );
            if( $value ) {
                update_post_meta( $post_id, $key, $value );
            }
			
            if( is_array( $value ) ) {
                $meta_prefix_assoc_key_deleted = false; // flag set for single trigger
                foreach( $value as $subkey => $sub_val ) {
                    // break serialize data into separated meta keys
                    if( is_numeric($subkey) ) {
                        if( $sub_val ) {
                            $subkey = str_replace( '$', '', $sub_val );
                            $subkey = str_replace( '-', '_', $subkey );
                            $subkey = str_replace( ' ', '_', $subkey );
                            $subkey = strtolower( $subkey );
                            $metakey = $key. '_'. strtolower( $subkey );

                            // Delete all meta values related to this metakey prefix
                            if( !$meta_prefix_assoc_key_deleted ) {
                                self::delete_metadata_with_prefix( $post_id, $key );
                                $meta_prefix_assoc_key_deleted = true;
                            }
                        
                            update_post_meta( $post_id, $metakey, 1 );
                        }
                    }else{
                        $subkey = str_replace( '$', '', $subkey );
                        $subkey = '_' .$key. '_'. strtolower( $subkey );
                        if( $sub_val ) {
                            update_post_meta( $post_id, $subkey, $sub_val );
                        }
                    }
                     
                }
            }
		}
        if( in_array( $for_module, array( 'Vendors' ) ) ) {
            // Update FAQs data 
            self::do_update_meta_faq( $post_id, $data );
        }
	}

    /**
	 * Update post meta FAQ data
	 * @param int $post_id
	 * @param array $data
	 * @return int|bool
	 */
	public static function do_update_meta_faq( $post_id, $data = array() ) {
        $record_id = $data['id'];
        $related_faqs = M118_Zoho_API::get_related_records( $record_id, 'Vendors', 'FAQ' ); // Update FAQs data from Zoho CRM CustomModule7
        if( $related_faqs && isset( $related_faqs['data'] ) && count( $related_faqs['data'] ) >= 1 ) { 
            $lender_faqs = $related_faqs['data'];
            $faq_data = array();
            foreach ( $lender_faqs as $key => $record ) {
                $faq = array();
                $faq['faq_id'] = ( isset( $record['id'] ) && $record['id'] ) ? $record['id'] : '';
                $faq['question'] = ( isset( $record['Name'] ) && $record['Name'] ) ? $record['Name'] : '';
                $answer = ( isset( $record['Answer_2'] ) && $record['Answer_2'] ) ? $record['Answer_2'] : $record['Answer'];
                $faq['answer'] = $answer;

                $faq_data[] = $faq;
            }

            if( $faq_data ) {
                update_post_meta( $post_id, '_faq_data', $faq_data );
            }
        }
	}


    /**
	 * Update post taxonomy data
	 * @param int $post_id
	 * @param array $data
	 * @return int|bool
	 */
	public static function do_update_taxonomy( $post_id, $data = array() ) {
        // check lender associated products
        $record_id = $data['id'];
        self::do_update_lender_products( $post_id, $record_id );

        // update property type
        if( isset( $data['Property_Type'] ) && $data['Property_Type'] ) {
            wp_delete_object_term_relationships( $post_id, 'property_type' );
            wp_set_object_terms( $post_id, $data['Property_Type'], 'property_type' );
        }
	}

    /**
	 * Update post attachments
	 * @param int $post_id
	 * @param array $data
	 * @return int|bool
	 */
	public static function do_update_attachments( $post_id, $data = array() ) {
        // before proceed do deletion
        self::delete_all_attachments( $post_id );

        $record_id = $data['id'];
        // record image
		$imagedata = M118_Zoho_API::get_record_image( $record_id, 'Vendors');
        if( $imagedata ) {
        	$base64_img_data = base64_encode($imagedata );
        	$attach_id = self::base64_save_image( $base64_img_data, $post_id, $record_id );
            // update record image as post thumbnail
        	set_post_thumbnail( $post_id, $attach_id );
        }
        // attachments
        $post_attachments_ids = array();
        $attachmentsdata = M118_Zoho_API::get_record_attachments( $record_id, 'Vendors' );
        if( isset( $attachmentsdata['data'] ) ) {
            foreach( $attachmentsdata['data'] as $attachment ) {
                $id = $attachment['id'];
                $filename = $attachment['File_Name'];
                $attachmentimagedata = M118_Zoho_API::get_record_attachments( $record_id, 'Vendors', $id );
                if( $attachmentimagedata ) {
                    $base64_attach_img_data = base64_encode($attachmentimagedata );
                    $attach_id = self::base64_save_image( $base64_attach_img_data, $post_id, $record_id, $filename );
                    $post_attachments_ids[] = $attach_id;
                }
            }
        }
        if( $post_attachments_ids ) {
            update_post_meta( $post_id, '_zoho_attachments_ids', $post_attachments_ids );
        }
	}

    /**
	 * Update term attachments
	 * @param int $term_id
	 * @param array $data
	 * @return int|bool
	 */
	public static function do_update_term_attachments( $term_id, $data = array() ) {
        // before proceed do deletion
        self::delete_all_term_attachments( $term_id );

        $record_id = $data['id'];
        // set no parent as its dealing with term_id which may conflict with core post object 
        // if we are passing term_is as post id
        $post_id = 0; 

        // record image
		$imagedata = M118_Zoho_API::get_record_image( $record_id, 'Products');
        if( $imagedata ) {
        	$base64_img_data = base64_encode($imagedata );
        	$attach_id = self::base64_save_image( $base64_img_data, $post_id, $record_id );
            // sync $term_id with $attach_id Post for term_id related attachments fetch
            // Its a kind of attachment post parent save
            update_post_meta( $attach_id, '_parent_object_id', $term_id );

            // update record image as term logo
        	update_term_meta( $term_id, '_term_image_id', $attach_id );
        }
        // attachments
        $term_attachments_ids = array();
        $attachmentsdata = M118_Zoho_API::get_record_attachments( $record_id, 'Products' );
        if( isset( $attachmentsdata['data'] ) ) {
            foreach( $attachmentsdata['data'] as $attachment ) {
                $id = $attachment['id'];
                $filename = $attachment['File_Name'];
                $attachmentimagedata = M118_Zoho_API::get_record_attachments( $record_id, 'Products', $id );
                if( $attachmentimagedata ) {
                    $base64_attach_img_data = base64_encode($attachmentimagedata );
                    $attach_id = self::base64_save_image( $base64_attach_img_data, $post_id, $record_id, $filename );
                    // sync $term_id with $attach_id Post for term_id related attachments fetch
                    // Its a kind of attachment post parent save
                    update_post_meta( $attach_id, '_parent_object_id', $term_id );

                    $term_attachments_ids[] = $attach_id;
                }
            }
        }
        if( $term_attachments_ids ) {
            update_term_meta( $term_id, '_term_attachments_ids', $term_attachments_ids );
        }
	}

    /**
	 * Delete all post attachments
	 * @param int $post_id
	 * @param array $data
	 * @return int|bool
	 */
	public static function do_update_lender_products( $post_id, $record_id = '' ) {
        $related_products = M118_Zoho_API::get_related_records( $record_id, 'Vendors' ); // get related products
        if( $related_products && isset( $related_products['data'] ) && count( $related_products['data'] ) >= 1 ) {
            $lender_products = $related_products['data'];
            wp_delete_object_term_relationships( $post_id, 'lender_product' );
            $product_term_ids = array();
            foreach ( $lender_products as $key => $product ) {
                $slug = sanitize_title_with_dashes( $product['Product_Name'] );
                $term = term_exists( $slug, 'lender_product' );
                $term_id = null;
			    if( isset( $term['term_id'] ) ) {
                    $term_id = $term['term_id'];
                } else {
                    $response = wp_insert_term(
                        $product['Product_Name'],
                        'lender_product', 
                        array(
                            'slug' => $slug,
                        )
                    );
    
                    if( isset( $response['term_id'] ) ) {
                        $term_id = $response['term_id'];
                    }
                }

                if( $term_id ) {
                    $product_term_ids[] = (int)$term_id;
                    self::do_update_termmeta( $term_id, $product );
                    self::do_update_term_attachments( $term_id, $product );
                    doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Data > do_update_lender_products sync done for term- '.$term_id.' and product - '.$product['id'] );
                }
            }
            if( $product_term_ids )
                wp_set_object_terms( $post_id, $product_term_ids, 'lender_product' );
        }
    }

    /**
	 * Update term meta data
	 * @param int $term_id
	 * @param array $data
	 * @return int|bool
	 */
	public static function do_update_termmeta( $term_id, $data = array() ) {
		foreach( $data as $key => $value ) {
            $key = str_replace( '$', '', $key );
			$key = '_' . strtolower( $key );
            if( $value ) {
                update_term_meta( $term_id, $key, $value );
            }
			
            if( is_array( $value ) ) {
                foreach( $value as $subkey => $sub_val ) {
                    if( is_numeric($subkey) ) break;
                    $subkey = str_replace( '$', '', $subkey );
                    $subkey = '_' .$key. '_'. strtolower( $subkey );
                    if( $sub_val ) {
                        update_term_meta( $term_id, $subkey, $sub_val );
                    }
                }
            }
		}
	}

    /**
	 * Delete all post attachments
	 * @param int $post_id
	 * @param array $data
	 * @return int|bool
	 */
	public static function delete_all_attachments( $post_id ) {
        $attachments = get_attached_media( '', $post_id );
        foreach ( $attachments as $attachment ) {
            wp_delete_attachment( $attachment->ID, 'true' );
        }
    }

    /**
	 * Delete all post attachments associated with meta parent
	 * @param int $object_id
	 * @param array $data
	 * @return int|bool
	 */
	public static function delete_all_term_attachments( $object_id ) {
        $attachment_ids = self::get_post_id_by( '_parent_object_id', $object_id, true );
        if( $attachment_ids ) :
            foreach ( $attachment_ids as $attachment_id ) {
                wp_delete_attachment( $attachment_id, 'true' );
            }
        endif;
    }

    /**
	 * get base64 string mime types and extention
	 * @param string $base64_img_encoded
	 * @return array
	 */
	public static function base64_mime_ext( $base64_img_encoded ) {
        $imgdata = base64_decode($base64_img_encoded);
        $finfo = finfo_open();
        $mime_type = finfo_buffer($finfo, $imgdata, FILEINFO_MIME_TYPE);
        if( $mime_type == 'image/svg' ) {
            return array('mimetype' => 'image/svg+xml', 'ext' => 'svg' );
        }
        
        $wp_mime_types = wp_get_mime_types();
        $extention = array_search( $mime_type, $wp_mime_types );
        
		if( $extention ) {
            return array('mimetype' => $mime_type, 'ext' => $extention );
        }
        return false;
    }

    /**
	 * save image data to attachment
	 * @param string $base64_img encoded
	 * @param int $post_id
     * @param int $record_id
     * @param string $imgname
	 * @return int|bool
	 */
	public static function base64_save_image( $base64_img, $post_id = 0, $record_id = '', $imgname = '' ) {
		// Upload dir.
        $upload_dir  = wp_upload_dir();
        $upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;
        
        $title = uniqid();
        if( $imgname ) {
            $name_part = explode( '.', $imgname );
            if( isset( $name_part[0] ) ) {
                $title = $name_part[0];
            }
        } 
        
        if( $record_id ) {
            $title = $record_id . '_' . $title;
        }

        $ext = 'jpeg';
        $file_type       = 'image/jpeg';

        $mime_ext = self::base64_mime_ext( $base64_img );
        doLogM118Zoho( 'Logged @' .date("Y-m-d H:i:s").' - M118_Zoho_Data > base64_save_image mime_ext- '.serialize($mime_ext) );
        if( $mime_ext ) {
            $ext = $mime_ext['ext'];
            $file_type = $mime_ext['mimetype'];
        }

        $img             = str_replace( 'data:image/jpeg;base64,', '', $base64_img );
        $img             = str_replace( ' ', '+', $img );
        $decoded         = base64_decode( $img );
        $filename        = $title . '.' . $ext;
        //$file_type       = 'image/jpeg';
        $hashed_filename = md5( $filename . microtime() ) . '_' . $filename;

        // Save the image in the uploads directory.
        $upload_file = file_put_contents( $upload_path . $hashed_filename, $decoded );

        $attachment = array(
            'post_mime_type' => $file_type,
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $hashed_filename ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'guid'           => $upload_dir['url'] . '/' . basename( $hashed_filename )
        );

        $attach_id = wp_insert_attachment( $attachment, $upload_dir['path'] . '/' . $hashed_filename, $post_id );

        // Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        
        // Generate the metadata for the attachment, and update the database record.
        $attach_data = wp_generate_attachment_metadata( $attach_id, $upload_dir['path'] . '/' . $hashed_filename );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        return $attach_id;
	}

    /**
	 * Get post id from meta key and value
	 * @param string $key
	 * @param mixed $value
     * @param bool $multiple_ids
	 * @return int|bool
	 */
	public static function get_post_id_by( $key, $value, $multiple_ids = false ) {
		global $wpdb;
		$meta = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key='".$key."' AND meta_value='".$value."'" );
		if( $multiple_ids && $meta ) {
            $post_ids = wp_list_pluck( $meta, 'post_id' );
            return $post_ids;
        }else {
            if ( is_array($meta) && !empty( $meta ) && isset( $meta[0] ) ) {
                $meta = $meta[0];
            }			
            if ( $meta && is_object( $meta ) ) {
                return $meta->post_id;
            }else {
                return false;
            }
        }
	}

    /**
	 * Delete transient with prefix
	 * @param string $prefix
	 * @return int|bool
	 */
	public static function delete_prefix_transients( $prefix ) {
        $transient_keys = self::get_transients_with_prefix( $prefix );
        foreach ( $transient_keys as $key ) {
            delete_transient( $key );
        }

    }

    /**
	 * Get transient with prefix
	 * @param string $prefix
	 * @return array
	 */
    public static function get_transients_with_prefix( $prefix ) {
        global $wpdb;
    
        $prefix = $wpdb->esc_like( '_transient_' . $prefix );
        $sql    = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s'";
        $keys   = $wpdb->get_results( $wpdb->prepare( $sql, $prefix . '%' ), ARRAY_A );
    
        if ( is_wp_error( $keys ) ) {
            return [];
        }
    
        return array_map( function( $key ) {
            // Remove '_transient_' from the option name.
            return ltrim( $key['option_name'], '_transient_' );
        }, $keys );
    }

    /**
	 * Delete meta data with prefix key
     * @param int $post_id
	 * @param string $prefix
	 * @return array
	 */
    public static function delete_metadata_with_prefix( $post_id, $prefix = '' ) {
        global $wpdb;

        if( !$prefix ) return;

        $prefix = $prefix .'_';

        $sql = $wpdb->remove_placeholder_escape( $wpdb->prepare("
            DELETE FROM $wpdb->postmeta
            WHERE `post_id` = %d 
            AND meta_key LIKE '$prefix%%';
            ", 
            $post_id
        ) ); 
        
        $result = $wpdb->query( $sql );

    }

}

return new M118_Zoho_Data();