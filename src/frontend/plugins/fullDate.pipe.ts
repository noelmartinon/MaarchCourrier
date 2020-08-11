import { Pipe, PipeTransform } from "@angular/core";
import { LANG } from '../app/translate.component';
import { TranslateService } from '@ngx-translate/core';
import { FunctionsService } from "../service/functions.service";

@Pipe({
	name: 'fullDate',
	pure: false
})
export class FullDatePipe implements PipeTransform {
	lang: any = LANG;
	constructor(
		private translate: TranslateService,
		public functions: FunctionsService
	) { }
	transform(value: string) {
		if (!this.functions.empty(value)) {
			const date = new Date(value);
			const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric' };
			return this.translate.instant('lang.onRange')[0].toUpperCase() + this.translate.instant('lang.onRange').substr(1).toLowerCase() + ' ' + date.toLocaleDateString(this.translate.instant('lang.langISO'), options);
		} else {
			return '';
		}
	}
}