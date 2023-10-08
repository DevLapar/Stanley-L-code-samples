import { SoundConfigIFace, SoundOptionsIFace } from "./sound.manager.iface";
/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * Sound model classes 
 */
export class SoundConfig {
	constructor({ name, master, options, source, label }: SoundConfigIFace) {
		this.name = name;
		this.master = master;
		this.options = options;
		this.source = source;
		this.label = label;
	}

	name: string;
	master: boolean;
	options: SoundOptionsIFace;
	source: HTMLAudioElement;
	label: string;

	setSoundOutputVolume(parent: SoundConfig = null) {
		if (!this.source) return;

		if (parent && parent.options.volume <= this.options.volume) this.source.volume = parent.options.volume;
		else this.source.volume = this.options.volume;
	}
}

export class SoundChannel {
	name: string = 'dymamic';
	main: SoundConfig;
	outputs: SoundConfig[] = [];
	label: string = 'Dynamic channel';
	info: string;
	toggled?: boolean = false;
}
