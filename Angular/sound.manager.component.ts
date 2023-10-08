import { Component, OnInit } from '@angular/core';
import { SoundManagerService } from './sound.manager.service';
/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * SoundManagerComponent component class to adjust volumes of SoundConfig outputs
 */
@Component({
	selector: 'sound-manager',
	templateUrl: './sound.manager.html',
})
export class SoundManagerComponent implements OnInit {
	constructor(protected soundService: SoundManagerService) { }

	ngOnInit(): void { }
}
