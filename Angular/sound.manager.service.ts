import { Injectable } from '@angular/core';
import { SoundChannel, SoundConfig } from './sound.manager.models';
import { SoundConfigIFace, SoundOptionsIFace } from './sound.manager.iface';
import { GlobalClientService } from 'src/app/global.client.service';
/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * SoundManagerService is a utility class that manages the audio output 
 */
@Injectable()
export class SoundManagerService {
	public channels: SoundChannel[] = [];

	/**Fallback/default sound channels */
	public baseChannels: SoundChannel[] = [
		{ name: 'ambience', label: 'Ambience sounds', info: 'Background sounds & music', outputs: [], main: null },
		{ name: 'buttons', label: 'Button sounds', info: 'Button press sounds', outputs: [], main: null },
		{ name: 'misc', label: 'Miscellaneous sounds', info: 'Celebration sounds', outputs: [], main: null },
		{ name: 'activity', label: 'Activity sounds', info: 'Exercise, exam, vocabulary sounds', outputs: [], main: null },
	];

	constructor(
		private gcService: GlobalClientService
	) {}

	/**Function that removes audio source */
	removeSoundOutput(name: string, channel: string) {

		/**Find designated sound channel */
		const pChannel = this.channels.find(c => c.name === channel);
		if (pChannel) {
			const soundOutputIx = pChannel.outputs.findIndex(o => o.name === name);
			if (soundOutputIx > -1) {
				const soundOutput = pChannel.outputs[soundOutputIx];
				const soundSrc = soundOutput.source;

				/**Destroy Audio element and remove from channel outputs */
				if (soundSrc) {
					soundSrc.pause();
					soundSrc.remove();
				}
				pChannel.outputs.splice(soundOutputIx, 1);
			}
		}
	}

	/**Returns SoundConfig from channel */
	getChannelSoundOutput(channel: SoundChannel, name: string): SoundConfig {
		return channel.outputs.find(o => o.name === name);
	}

	/**Checks if SoundConfig exists, returns boolean */
	soundOutputExists(channel: SoundChannel, output: string): boolean {
		return !!this.getChannelSoundOutput(channel, output);
	}

	/**Checks if SoundChannel exists, returns boolean */
	channelExists(channel: string): boolean {
		return !!this.getChannel(channel);
	}

	getChannel(name: string): SoundChannel {
		return this.channels.find(c => c.name === name);
	}

	addSoundChannel(channel: SoundChannel): SoundChannel {
		this.channels.push(channel);
		return channel;
	}

	/**Creates new SoundChannel and ads a main SoundConfig output, returns SoundChannel */
	createSoundChannel(name: string): SoundChannel {
		const channel: SoundChannel = new SoundChannel();
		channel.name = name + '_channel';

		this.addMainChannel(channel);
		return channel;
	}

	/**Creates new SoundConfig and ads to SoundChannel as main controller, returns SoundConfig */
	addMainChannel(channel: SoundChannel): SoundConfig {
		const mainOutput: SoundConfig = this.createSoundOutput(channel.name, channel.name, null, true);

		channel.main = mainOutput;
		return mainOutput;
	}

	/**Creates SoundOptionsIFace and ads to SoundConfig as options, returns SoundOptionsIFace */
	addSoundOptions(output: SoundConfigIFace): SoundOptionsIFace {
		const soundOptions: SoundOptionsIFace = {
			volume: 1,
			loop: false,
			autoplay: false,
			controlllable: true,
		};

		output.options = soundOptions;
		return soundOptions;
	}

	addSoundToChannel(soundConfig: SoundConfig, channel: SoundChannel) {
		if (!Array.isArray(channel.outputs)) channel.outputs = [];
		channel.outputs.push(soundConfig);
	}

	/**Creates new SoundConfig and determines it initial volume strength, returns SoundConfig */
	createSoundOutput(name: string, label: string, options: SoundOptionsIFace, master: boolean = false): SoundConfig {
		const soundOut: SoundConfig = new SoundConfig({
			source: null,
			name,
			label,
			master: master,
			options: null
		});

		this.addSoundOptions(soundOut);

		if (options) soundOut.options = Object.assign(soundOut.options, options);

		/**Determine initial volume strength based on locally saved settings (accesses StorageMap) */
		const volumePref = this.getDeviceVolumePreference(name, false);
		soundOut.options.volume = volumePref && volumePref.volume ? volumePref.volume || 1 : 1;

		return soundOut;
	}

	/**Function that adds a new output based on a provided Audio source
	 * Then adds the output to a channel
	 */
	addSoundOutput(name: string, label: string, source: HTMLAudioElement | string, channel: string, options: SoundOptionsIFace) {
		const soundSrc = source instanceof HTMLAudioElement ? source : new Audio(source);
		const pChannel = this.channelExists(channel) ? this.getChannel(channel) : this.getBaseChannel(channel);
		const pSound = this.soundOutputExists(pChannel, name) ? this.getChannelSoundOutput(pChannel, name) : null;

		if (!pSound) {
			const soundOut: SoundConfig = this.createSoundOutput(name, label, options);
			const channelVol = pChannel.main.options.volume;
			const outputVol = soundOut.options.volume;
			soundSrc.volume = channelVol < outputVol ? channelVol : outputVol;
			if (soundOut.options.autoplay) soundSrc.play();
			if (soundOut.options.loop) soundSrc.onended = () => { soundSrc.play(); };

			soundOut.source = soundSrc;
			this.addSoundToChannel(soundOut, pChannel);
		}
	}

	/**Function to retrieve a base channel, if non existant create a new generic sound channel
	 * Returns a SoundChannel
	 */
	getBaseChannel(channel: string): SoundChannel {
		let baseChannel = this.baseChannels.find(c => c.name === channel);

		if (baseChannel) this.addMainChannel(baseChannel);
		else baseChannel = this.createSoundChannel(channel);

		const volumePref = this.getDeviceVolumePreference(baseChannel.name, true);
		baseChannel.main.options.volume = volumePref && volumePref.volume ? volumePref.volume || 1 : 1;

		return this.addSoundChannel(baseChannel);
	}

	/**Function to retrieve volume preferences that are saved locally (StorageMap) */
	getDeviceVolumePreferences(): Array<any> {
		const prefs = this.gcService.getClientOption('sound_output_volumes', true);
		return prefs ? prefs : [];
	}

	/**Function that returns volume preferences or its designated index */
	getDeviceVolumePreference(name, master, index = false): any {
		const deviceVolumePrefs: any[] = this.getDeviceVolumePreferences();
		const volumePrefIx = Array.isArray(deviceVolumePrefs) ? deviceVolumePrefs.findIndex((vp: any) => vp.name === name && vp.master === master) : -1;
		const volumePref = volumePrefIx > -1 ? deviceVolumePrefs[volumePrefIx] : null;
		return index ? volumePrefIx : volumePref ? volumePref : 1;
	}

	/**Function that returns a parent channel based on SoundConfigs name */
	getParentChannel(output: SoundConfig): SoundChannel {
		const name = output.name;
		const pChannel = this.channels.find(c => c.outputs.find(so => so.name === name));
		return pChannel;
	}

	/**Function adjusts the volume of a main SoundChannel or SoundConfig */
	adjustVolume(output: SoundConfig) {
		const isMaster = output.master;
		if (isMaster) {
			const channel = this.getChannel(output.name);
			const cOptions = output.options;
			const cOutputs = channel.outputs;
			this.saveVolumePreference(channel.name, channel.main.options.volume, true);

			/**If the main output volume is lower than any of its children, also lower down the volume of children */
			cOutputs.forEach(so => {
				if (cOptions.volume <= so.options.volume) this.setSoundOutputVolume(so, channel.main);
			});
		} else {
			/**Adjust soundConfig volume and parent channel Audio element volume */
			const parentChannel = this.getParentChannel(output)?.main;
			this.setSoundOutputVolume(output, parentChannel);
		}
	}

	setSoundOutputVolume(output: SoundConfig, parentOutput: SoundConfig) {
		this.saveVolumePreference(output.name, output.options.volume);
		output.setSoundOutputVolume(parentOutput);
	}

	/**Saves the volume preference locally */
	saveVolumePreference(name, volume, master = false) {
		const volumePreference = {
			name, volume, master
		};
		const prefIx = this.getDeviceVolumePreference(name, master, true);
		const prefs = this.getDeviceVolumePreferences();
		if (prefIx > -1) prefs[prefIx] = volumePreference;
		else prefs.push(volumePreference);

		this.gcService.updateClientOption('sound_output_volumes', prefs, true);
	}
}