/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * Sound configs interfaces
 */
export interface SoundConfigIFace {
	name: string;
	master: boolean;
	options: SoundOptionsIFace;
	source: HTMLAudioElement;
	label?: string;
}

export interface SoundOptionsIFace {
	volume: number;
	loop: boolean;
	autoplay: boolean;
	controlllable?: boolean;
}