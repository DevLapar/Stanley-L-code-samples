/**
 * Author: Stanley
 * Company: Aloha Shaka (www.alohashaka.com)
 * EndlessScroll is an utility class that transforms a vertical scrollable page into a horizontal scrollable page 
 * Live demonstration: www.stanley.fyi/#slp-projects
 */
class EndlessScroll {
	endlessPage;
	endlessWrapper;
	endlessScroller;
	endlessContent;
	timelineContainer;
	mobileSize;
	isMobile;
	docWidth = document.body.clientWidth;
	scrollDirection;
	lastScrollLeft;
	inited = false;

	constructor(endlessPage, callbackFn) {
		this.isMobile = asCore.isConsideredMobile();

		/** Init required HTMLElements based on designated element (page)*/
		this.endlessPage = endlessPage;
		this.endlessWrapper = this.endlessPage.querySelector('.slpe-wrapper');
		this.endlessScroller = this.endlessPage.querySelector('.slpew-scroller');
		this.endlessContent = this.endlessPage.querySelector('.slpew-endless');
		this.timelineContainer = this.endlessPage.querySelector('.sl-timeline-container');
		this.scrollWidth = this.endlessScroller.scrollWidth;

		this.initEndless();

		let throttle = asCore.addThrottle(this.isMobile ? 5 : 0);

		/** Reinit after window has changed in size*/
		asCore.addEvent(window, 'resize', () => asCore.debounce(() => {
			asCore.clearThrottle(throttle.id);
			this.isMobile = asCore.isConsideredMobile();
			throttle = asCore.addThrottle(this.isMobile ? 5 : 0);
			this.initEndless();
		}, 10));

		/** Execute scroll event based on a throttle (for performance)*/
		asCore.onScroll((e) => asCore.throttle(throttle, () => this.scrollEv(e, callbackFn)));
	}

	/** Init essential data*/
	initEndless() {
		const calcHeight = this.calcPageHeight();
		this.endlessPage.style.height = calcHeight + 'px';
	}

	calcPageHeight() {
		const ofw = this.endlessContent.offsetWidth;
		const ih = window.innerHeight;
		const iw = window.innerWidth;
		return this.isMobile ? ofw + iw : ofw - ih;
	}

	/** Function that executes on scroll event and as long as it is the active page
	 * Also executes related callback event
	*/
	scrollEv(e, callback) {
		const st = this.endlessScroller.pageXOffset || this.endlessScroller.scrollLeft;
		this.scrollDirection = (st > this.lastScrollLeft) ? 'right' : 'left';
		this.lastScrollLeft = st <= 0 ? 0 : st;
		if (this.endlessPage === asCore.activePage || !this.inited) {
			if (!this.inited) this.inited = true;
			this.endlessScroller.scrollLeft = this.endlessWrapper.offsetTop;
			if (callback) callback(e);
		}
	}
}
