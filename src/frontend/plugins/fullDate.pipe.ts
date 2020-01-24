import { Pipe, PipeTransform } from "@angular/core";
import { LANG } from '../app/translate.component';
import { FunctionsService } from "../service/functions.service";

@Pipe({
	name: 'fullDate',
	pure: false
})
export class FullDatePipe implements PipeTransform {
	lang: any = LANG;
	constructor(
		public functions: FunctionsService
	) { }
	transform(value: string) {
		if (!this.functions.empty(value)) {
			const date = new Date(value);
			const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric' };
			return this.lang.onRange[0].toUpperCase() + this.lang.onRange.substr(1).toLowerCase() + ' ' + date.toLocaleDateString(this.lang.langISO, options);
		} else {
			return '';
		}
	}
}