/**
 * Career Manager Plugin — Admin Metabox & Settings Script
 *
 * Handles:
 * 1. Expiration date checkbox toggle (`#career-enable-exp-toggle`)
 * 2. Multi-file Media Library uploader repeater (`#career-add-file-btn`)
 * 3. Copy Direct Link / Force Download Link / Shortcode buttons (`.career-copy-url-btn`)
 */
( function ( $ ) {
	'use strict';

	$( document ).ready( function () {
		// 1. Toggle Expiration Date fields
		$( '#career-enable-exp-toggle' ).on( 'change', function () {
			$( '#career-expiration-fields-wrap' ).toggle( this.checked );
		} );

		// 2. Copy URL / Shortcode button
		$( document ).on( 'click', '.career-copy-url-btn', function ( e ) {
			e.preventDefault();
			const $btn = $( this );
			const $input = $btn.siblings( '.career-file-url-input' );
			if ( $input.length ) {
				$input[ 0 ].select();
				document.execCommand( 'copy' );
				const oldText = $btn.text();
				$btn.text( '[เรียบร้อย] คัดลอกแล้ว!' );
				setTimeout( function () {
					$btn.text( oldText );
				}, 2000 );
			}
		} );

		// 3. Remove file row
		$( document ).on( 'click', '.career-remove-file-btn', function ( e ) {
			e.preventDefault();
			$( this ).closest( '.career-file-item-row' ).remove();
		} );

		// 4. Add file attachment via wp.media
		let fileFrame;
		$( '#career-add-file-btn' ).on( 'click', function ( e ) {
			e.preventDefault();
			if ( typeof wp === 'undefined' || ! wp.media ) {
				return;
			}

			fileFrame = wp.media( {
				title: 'เลือกหรืออัปโหลดไฟล์แนบ (*.pdf, *.zip, *.rar, *.docx)',
				button: { text: 'เลือกไฟล์นี้' },
				multiple: true,
			} );

			fileFrame.on( 'select', function () {
				const selection = fileFrame.state().get( 'selection' );
				selection.each( function ( attachment ) {
					const data = attachment.toJSON();
					const fileId = data.id || 0;
					const fileTitle = data.title || data.filename || 'Document';
					const fileUrl = data.url || '';
					const fileExt = ( ( data.filename || '' ).split( '.' ).pop() || 'FILE' ).toUpperCase();
					const fileSize = data.filesizeHumanReadable || '';
					const idx = $( '#career-files-list-wrap .career-file-item-row' ).length;
					const downloadUrl = window.location.origin + '/?career_download_file=' + fileId;

					const html =
						'<div class="career-file-item-row" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">' +
						'<div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px;">' +
						'<div style="display: flex; align-items: center; gap: 10px;">' +
						'<span style="background: #0f72dc; color: #ffffff; font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; text-transform: uppercase;">' +
						fileExt +
						'</span>' +
						'<strong style="font-size: 14px; color: #0f294a;">' +
						$( '<div>' ).text( fileTitle ).html() +
						'</strong>' +
						( fileSize ? '<span style="font-size: 12px; color: #64748b;">(' + fileSize + ')</span>' : '' ) +
						'</div>' +
						'<button type="button" class="button button-link-delete career-remove-file-btn" style="color: #dc2626; font-weight: 600;">ลบไฟล์แนบนี้</button>' +
						'</div>' +
						'<div style="display: flex; flex-direction: column; gap: 8px;">' +
						'<div style="display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0;">' +
						'<span style="font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap; width: 170px;">Generated Direct Link:</span>' +
						'<input type="text" readonly value="' +
						fileUrl +
						'" class="career-file-url-input" style="flex: 1; background: #ffffff; font-family: monospace; font-size: 12px; padding: 4px 8px; border-radius: 6px; border: 1px solid #cbd5e1;" />' +
						'<button type="button" class="button button-secondary career-copy-url-btn" style="font-weight: 700; color: #0f72dc; border-color: #0f72dc;">[Copy] คัดลอกลิงก์ตรง (Direct URL)</button>' +
						'</div>' +
						'<div style="display: flex; align-items: center; gap: 8px; background: #eff6ff; padding: 8px 12px; border-radius: 8px; border: 1px solid #bfdbfe;">' +
						'<span style="font-size: 12px; font-weight: 700; color: #1e40af; white-space: nowrap; width: 170px;">Generated Download Link:</span>' +
						'<input type="text" readonly value="' +
						downloadUrl +
						'" class="career-file-url-input" style="flex: 1; background: #ffffff; font-family: monospace; font-size: 12px; padding: 4px 8px; border-radius: 6px; border: 1px solid #93c5fd; color: #1e40af; font-weight: 700;" />' +
						'<button type="button" class="button button-secondary career-copy-url-btn" style="font-weight: 700; color: #1e40af; border-color: #93c5fd;">[Copy] คัดลอกลิงก์ดาวน์โหลด (Force Download)</button>' +
						'</div>' +
						'</div>' +
						'<input type="hidden" name="career_file_attachments[' +
						idx +
						'][id]" value="' +
						fileId +
						'" />' +
						'<input type="hidden" name="career_file_attachments[' +
						idx +
						'][title]" value="' +
						$( '<div>' ).text( fileTitle ).html() +
						'" />' +
						'<input type="hidden" name="career_file_attachments[' +
						idx +
						'][url]" value="' +
						fileUrl +
						'" />' +
						'<input type="hidden" name="career_file_attachments[' +
						idx +
						'][ext]" value="' +
						fileExt.toLowerCase() +
						'" />' +
						'<input type="hidden" name="career_file_attachments[' +
						idx +
						'][size]" value="' +
						fileSize +
						'" />' +
						'</div>';

					$( '#career-files-list-wrap' ).append( html );
				} );
			} );

			fileFrame.open();
		} );
	} );
} )( jQuery );
