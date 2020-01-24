import { Injectable } from '@angular/core';
import { LANG } from '../app/translate.component';

@Injectable()
export class FunctionsService {

    lang: any = LANG;

    constructor() { }

    empty(value: any) {
        if (value === null || value === undefined) {
            return true;

        } else if (Array.isArray(value)) {
            if (value.length > 0) {
                return false;
            } else {
                return true;
            }
        } else if (String(value) !== '') {
            return false;
        } else {
            return true;
        }
    }

    formatFrenchDateToTechnicalDate(date: string) {
        if (!this.empty(date)) {
            let arrDate = date.split('-');
            arrDate = arrDate.concat(arrDate[arrDate.length-1].split(' '));
            arrDate.splice(2,1);

            if (this.empty(arrDate[3])) {
                arrDate[3] = '00:00:00';
            }
     
            const formatDate = `${arrDate[2]}-${arrDate[1]}-${arrDate[0]} ${arrDate[3]}`;
    
            return formatDate;
        } else {
            return date;
        }
    }

    formatDateObjectToDateString(date: Date, limitMode: boolean = false, format:string = 'dd-mm-yyyy') {
        if (date !== null) {
            let formatDate: any[] = [];
            format.split('-').forEach((element: any) => {
                if (element === 'dd') {
                    let day: any = date.getDate();
                    day = ('00' + day).slice(-2);
                    formatDate.push(day);
                } else if (element === 'mm') {
                    let month: any = date.getMonth() + 1;
                    month = ('00' + month).slice(-2);
                    formatDate.push(month);
                } else if (element === 'yyyy') {
                    let year: any = date.getFullYear();
                    formatDate.push(year);
                }
            });

            let limit = '';
            if (limitMode) {
                limit = ' 23:59:59';
            }
            return `${formatDate.join('-')}${limit}`;
        } else {
            return date;
        }
    }
}
