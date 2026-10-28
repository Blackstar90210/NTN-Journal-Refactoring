/**
 * NTN Journal - Gutenberg Plugin per la generazione del Riassunto tramite IA
 */

( function( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { Button, Spinner, Notice } = wp.components;
	const { useState } = wp.element;
	const { useDispatch, useSelect } = wp.data;
	const apiFetch = wp.apiFetch;

	const AISummaryPanel = () => {
		const [ isGenerating, setIsGenerating ] = useState( false );
		const [ error, setError ] = useState( null );
		
		const { editPost } = useDispatch( 'core/editor' );
		const { createSuccessNotice, createErrorNotice } = useDispatch( 'core/notices' );
		
		const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId(), [] );
		const currentExcerpt = useSelect( ( select ) => select( 'core/editor' ).getEditedPostAttribute( 'excerpt' ), [] );
		
		const handleGenerate = async () => {
			setIsGenerating( true );
			setError( null );
			
			try {
				const response = await apiFetch( {
					path: '/ntn/v1/generate-summary',
					method: 'POST',
					data: { post_id: postId },
				} );
				
				if ( response.success && response.excerpt ) {
					// Aggiorna l'estratto
					editPost( { 
						excerpt: response.excerpt,
						meta: {
							_is_ai_excerpt: '1',
							_ai_generated_excerpt_text: response.excerpt
						}
					} );
					
					createSuccessNotice( 'Riassunto AI generato con successo!', {
						type: 'snackbar',
					} );
				}
			} catch ( err ) {
				const errorMessage = err.message || 'Errore sconosciuto durante la generazione.';
				setError( errorMessage );
				createErrorNotice( 'Errore AI: ' + errorMessage, {
					type: 'snackbar',
				} );
			} finally {
				setIsGenerating( false );
			}
		};

		return wp.element.createElement(
			PluginDocumentSettingPanel,
			{
				name: 'ntn-ai-summary-panel',
				title: 'Generazione AI (Riassunto)',
				icon: 'admin-customizer',
			},
			wp.element.createElement(
				'div',
				{ style: { padding: '10px 0' } },
				wp.element.createElement(
					'p',
					{ style: { fontSize: '13px', marginBottom: '15px' } },
					'Genera automaticamente un riassunto SEO (meta description) ottimizzato analizzando il contenuto dell\'articolo. Verrà aggiunto il badge // Estratto by AI.'
				),
				wp.element.createElement(
					Button,
					{
						isPrimary: true,
						onClick: handleGenerate,
						disabled: isGenerating,
						style: { width: '100%', justifyContent: 'center' }
					},
					isGenerating ? wp.element.createElement( Spinner, null ) : 'Genera Riassunto (Gemini/Groq)'
				),
				error && wp.element.createElement(
					'p',
					{ style: { color: '#d63638', marginTop: '10px', fontSize: '12px' } },
					error
				),
				currentExcerpt && !isGenerating && wp.element.createElement(
					'p',
					{ style: { color: '#007017', marginTop: '10px', fontSize: '12px', fontWeight: 'bold' } },
					'L\'estratto è attualmente compilato.'
				)
			)
		);
	};

	registerPlugin( 'ntn-ai-summary', {
		render: AISummaryPanel,
		icon: 'admin-customizer',
	} );

} )( window.wp );
