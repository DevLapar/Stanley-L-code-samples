/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * StickyScroll is a utility class enables a sticky scrolling effect 
 * where certain elements activate based on container scroll position
 * Live demonstration: www.stanley.fyi/#slp-about
 */
class StickyScroll {
	contentOverlay;
	actualContent;
	parentHolder;

	truthyFn;
	falsyFn;
	startXpos;
	threshold;
	stateVars = {};
	inited = false;

	constructor(contentOverlay, actualContent, parentHolder, threshold = 5) {

		/** Init required HTMLElements based on designated element (page)*/
		this.contentOverlay = contentOverlay;
		this.actualContent = actualContent;
		this.parentHolder = parentHolder;
		this.threshold = threshold;

		/** Important for a good sticky scroll is to define the height*/
		this.parentHolder.style.height = (this.actualContent.offsetHeight + asCore.viewportHeight) + 'px';

		/** Initializes on load, defines some defaults*/
		asCore.onLoad(() => {
			const overlayPos = this.contentOverlay.getBoundingClientRect();
			this.startXpos = this.contentOverlay.offsetTop === 0 ? asCore.scrollContainer.scrollTop + Math.abs(overlayPos.y) : (asCore.scrollContainer.scrollTop - this.contentOverlay.offsetTop - Math.abs(overlayPos.y));

			if (this.truthyFn) this.truthyFn();
			if (!this.inited) this.inited = true;

			/** Start listening to scroll events after page has loaded*/
			this.scrolling();
		});
	}

	/** Defines the truthy and falsy funtionality*/
	setScrollFns(truthyFn, falsyFn) {
		this.truthyFn = truthyFn;
		this.falsyFn = falsyFn;
	}

	/** Listen to scroll event*/
	scrolling() {

		/** Execute scroll event based on a throttle (for performance)*/
		const throttle = asCore.addThrottle(50);
		asCore.onScroll((e) => {
			asCore.throttle(throttle, () => {
				if (!this.inited) return;

				if (this.parentHolder === asCore.activePage || !this.inited) {
					if (!this.inited) this.inited = true;
					const startScrollDown = asCore.scrollContainer.scrollTop >= this.startXpos;
					const startScrollUp = asCore.scrollContainer.scrollTop - this.startXpos <= (this.contentOverlay.offsetTop + this.threshold);
					const stopScrollDown = this.contentOverlay.offsetTop !== 0 && ((this.startXpos + this.contentOverlay.offsetTop + this.threshold) < asCore.scrollContainer.scrollTop);
					const stopScrollUp = this.contentOverlay.offsetTop === 0 && (asCore.scrollContainer.scrollTop < this.startXpos);
					
					/**Based on scroll direction and scroll position/container position execute code */
					if ((asCore.scrollDirection === 'down' && !stopScrollDown && startScrollDown) || (asCore.scrollDirection === 'up' && !stopScrollUp && startScrollUp)) {
						if (this.truthyFn) this.truthyFn();
					} else if (this.falsyFn) this.falsyFn();
				}
			})
		});
	}

	determineActiveEle(arr, index, target) {
		const element = arr[index];
		const nextIx = index + 1;
		const nextEle = typeof arr[nextIx] !== 'undefined' ? arr[nextIx] : null;
		const nextOk = (nextEle && this.contentOverlay.offsetTop <= nextEle.offsetTop);
		const triggerTop = this.contentOverlay.offsetTop >= element.offsetTop;
		if (target !== element && triggerTop && (nextOk || !nextEle)) return element;
		return false;
	}
}