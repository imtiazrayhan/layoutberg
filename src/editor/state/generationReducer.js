/**
 * Generation state reducer for LayoutBerg editor.
 *
 * @package LayoutBerg
 * @since 1.0.0
 */

export const GENERATION_ACTIONS = {
	START: 'START_GENERATION',
	UPDATE_STATE: 'UPDATE_GENERATION_STATE',
	UPDATE_PROGRESS: 'UPDATE_GENERATION_PROGRESS',
	SUCCESS: 'GENERATION_SUCCESS',
	ERROR: 'GENERATION_ERROR',
	RESET: 'RESET_GENERATION',
};

export const initialGenerationState = {
	isGenerating: false,
	state: 'idle', // idle, preparing, sending, generating, processing, complete
	error: null,
	lastResponse: null,
	lastGeneratedBlocks: null,
	startTime: null,
	stepStartTime: null,
	progressDetails: {
		tokensUsed: 0,
		blocksGenerated: 0,
		validationErrors: 0,
	},
};

/**
 * Generation state reducer.
 *
 * @param {Object} state Current state.
 * @param {Object} action Action to dispatch.
 * @return {Object} New state.
 */
export function generationReducer( state, action ) {
	switch ( action.type ) {
		case GENERATION_ACTIONS.START:
			return {
				...state,
				isGenerating: true,
				state: 'preparing',
				error: null,
				startTime: Date.now(),
				stepStartTime: Date.now(),
				progressDetails: {
					tokensUsed: 0,
					blocksGenerated: 0,
					validationErrors: 0,
				},
			};

		case GENERATION_ACTIONS.UPDATE_STATE:
			return {
				...state,
				state: action.payload,
				stepStartTime: Date.now(),
			};

		case GENERATION_ACTIONS.UPDATE_PROGRESS:
			return {
				...state,
				progressDetails: action.payload,
			};

		case GENERATION_ACTIONS.SUCCESS:
			return {
				...state,
				isGenerating: false,
				state: 'complete',
				error: null,
				lastResponse: action.payload.response,
				lastGeneratedBlocks: action.payload.blocks,
			};

		case GENERATION_ACTIONS.ERROR:
			return {
				...state,
				isGenerating: false,
				state: 'idle',
				error: action.payload,
			};

		case GENERATION_ACTIONS.RESET:
			return initialGenerationState;

		default:
			return state;
	}
}

/**
 * Get state description for UI display.
 *
 * @param {string} state Current state.
 * @return {string} Human-readable state description.
 */
export function getStateDescription( state ) {
	const descriptions = {
		idle: 'Ready to generate',
		preparing: 'Preparing generation...',
		sending: 'Sending request...',
		generating: 'Generating layout...',
		processing: 'Processing response...',
		complete: 'Generation complete',
	};

	return descriptions[ state ] || 'Unknown state';
}

/**
 * Check if state is a loading state.
 *
 * @param {string} state Current state.
 * @return {boolean} True if loading.
 */
export function isLoadingState( state ) {
	return [ 'preparing', 'sending', 'generating', 'processing' ].includes(
		state
	);
}

/**
 * Check if state is an error state.
 *
 * @param {Object} state Current state object.
 * @return {boolean} True if error state.
 */
export function isErrorState( state ) {
	return state.error !== null;
}
