import { Pipe, PipeTransform, NgZone, ChangeDetectorRef, OnDestroy } from "@angular/core";
import { LANG } from '../app/translate.component';

@Pipe({
	name: 'timeAgo',
	pure: false
})
export class TimeAgoPipe implements PipeTransform, OnDestroy {
	private timer: number;
	lang: any = LANG;
	constructor(private changeDetectorRef: ChangeDetectorRef, private ngZone: NgZone) { }
	transform(value: string, args: string = null) {
		this.removeTimer();
		let d = new Date(value);
		let dayNumber = ('0' + d.getDate()).slice(-2);
		const realMonth = d.getMonth() + 1;
		let monthNumber = ('0' + realMonth).slice(-2);
		let hourNumber = ('0' + d.getHours()).slice(-2);
		let minuteNumber = ('0' + d.getMinutes()).slice(-2);
		let now = new Date();
		let month = [];
		month[0] = this.lang.januaryShort;
		month[1] = this.lang.februaryShort;
		month[2] = this.lang.marchShort;
		month[3] = this.lang.aprilShort;
		month[4] = this.lang.mayShort;
		month[5] = this.lang.juneShort;
		month[6] = this.lang.julyShort;
		month[7] = this.lang.augustShort;
		month[8] = this.lang.septemberShort;
		month[9] = this.lang.octoberShort;
		month[10] = this.lang.novemberShort;
		month[11] = this.lang.decemberShort;
		let seconds = Math.round(Math.abs((now.getTime() - d.getTime()) / 1000));
		let curentDayNumber = ('0' + now.getDate()).slice(-2);
		let timeToUpdate = (Number.isNaN(seconds)) ? 1000 : this.getSecondsUntilUpdate(seconds) * 1000;
		this.timer = this.ngZone.runOutsideAngular(() => {
			if (typeof window !== 'undefined') {
				return window.setTimeout(() => {
					this.ngZone.run(() => this.changeDetectorRef.markForCheck());
				}, timeToUpdate);
			}
			return null;
		});
		let minutes = Math.round(Math.abs(seconds / 60));
		let hours = Math.round(Math.abs(minutes / 60));
		let days = Math.round(Math.abs(hours / 24));
		let months = Math.round(Math.abs(days / 30.416));
		let years = Math.round(Math.abs(days / 365));
		if (value == this.lang.undefined) {
			return this.lang.undefined;
		} else if (Number.isNaN(seconds)) {
			return '';
		} else if (seconds <= 45) {
			return this.getFormatedDate(this.lang.dateAgo.toLowerCase(), this.lang.fewSeconds, args);
		} else if (seconds <= 90) {
			return this.getFormatedDate(this.lang.dateAgo.toLowerCase(), this.lang.oneMinute, args);
		} else if (minutes <= 45) {
			return this.getFormatedDate(this.lang.dateAgo.toLowerCase(), minutes + ' ' + this.lang.minutes, args);
		} else if (minutes <= 90) {
			return this.getFormatedDate(this.lang.dateAgo.toLowerCase(), this.lang.oneHour, args);
		} else if (hours <= 24 && dayNumber === curentDayNumber) {
			return this.getFormatedDate(this.lang.at.toLowerCase(), hourNumber + ':' + minuteNumber, args);
			//return hours + ' heures';
		} else if (hours <= 24) {
			return this.getFormatedDate(this.lang.dateAgo.toLowerCase(), days + ' ' + this.lang.dayS, args);
			//return 'un jour';
		} else if (days <= 5) {
			return this.getFormatedDate(this.lang.dateAgo.toLowerCase(), days + ' ' + this.lang.dayS, args);
			//return days + ' jours';
		} else if (days <= 345) {
			return this.getFormatedDate(this.lang.dateTo.toLowerCase(), d.getDate() + ' ' + month[d.getMonth()], args);
			//return months + ' mois';
		} else if (days <= 545) {
			return this.getFormatedDate(this.lang.dateTo.toLowerCase(), dayNumber + '/' + monthNumber + '/' + d.getFullYear(), args);
			//return 'un an';
		} else { // (days > 545)
			return this.getFormatedDate(this.lang.dateTo.toLowerCase(), dayNumber + '/' + monthNumber + '/' + d.getFullYear(), args);
			//return years + ' ans';
		}
	}
	ngOnDestroy(): void {
		this.removeTimer();
	}
	private removeTimer() {
		if (this.timer) {
			window.clearTimeout(this.timer);
			this.timer = null;
		}
	}
	private getSecondsUntilUpdate(seconds: number) {
		let min = 60;
		let hr = min * 60;
		let day = hr * 24;
		if (seconds < min) { // less than 1 min, update every 2 secs
			return 2;
		} else if (seconds < hr) { // less than an hour, update every 30 secs
			return 30;
		} else if (seconds < day) { // less then a day, update every 5 mins
			return 300;
		} else { // update every hour
			return 3600;
		}
	}

	getFormatedDate(prefix: string, content: string, mode: string) {
		if (mode === 'full') {
			return `${prefix} ${content}`;
		} else {
			return content;
		}
	}
}
