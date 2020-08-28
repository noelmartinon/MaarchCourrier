import { Injectable } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { LatinisePipe } from 'ngx-pipes';
import { AuthService } from './auth.service';
import { HeaderService } from './header.service';
import { TimeLimitPipe } from '../plugins/timeLimit.pipe';

@Injectable({
    providedIn: 'root'
})
export class FunctionsService {

    constructor(
        public translate: TranslateService,
        private authService: AuthService,
        private headerService: HeaderService,
        private latinisePipe: LatinisePipe,
    ) { }

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
            arrDate = arrDate.concat(arrDate[arrDate.length - 1].split(' '));
            arrDate.splice(2, 1);

            if (this.empty(arrDate[3])) {
                arrDate[3] = '00:00:00';
            }

            const formatDate = `${arrDate[2]}-${arrDate[1]}-${arrDate[0]} ${arrDate[3]}`;

            return formatDate;
        } else {
            return date;
        }
    }

    formatFrenchDateToObjectDate(date: string, delimiter: string = '-') {
        if (!this.empty(date)) {
            let arrDate = date.split(delimiter);
            arrDate = arrDate.concat(arrDate[arrDate.length - 1].split(' '));
            arrDate.splice(2, 1);

            if (this.empty(arrDate[3])) {
                arrDate[3] = '00:00:00';
            }

            const formatDate = `${arrDate[2]}-${arrDate[1]}-${arrDate[0]} ${arrDate[3]}`;

            return new Date(formatDate);
        } else {
            return date;
        }
    }

    formatDateObjectToDateString(date: Date, limitMode: boolean = false, format: string = 'dd-mm-yyyy') {
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

    listSortingDataAccessor(data: any, sortHeaderId: any) {
        if (typeof data[sortHeaderId] === 'string') {
            return data[sortHeaderId].toLowerCase();
        }
        return data[sortHeaderId];
    }

    filterUnSensitive(template: any, filter: string, filteredColumns: any) {
        let filterReturn = false;
        filter = this.latinisePipe.transform(filter);
        filteredColumns.forEach((column: any) => {
            let val = template[column];
            if (typeof template[column] !== 'string') {
                val = JSON.stringify(val);
            }
            filterReturn = filterReturn || this.latinisePipe.transform(val.toLowerCase()).includes(filter);
        });
        return filterReturn;
    }

    debug(msg: string, route: string) {
        let info: any = {
            route : route,
            session : 'No user logged !',
            refreshSession : 'No user logged !',
            user : 'No user logged !'
        };
        if (this.authService.getToken() != null) {
            info = {
                route : route,
                session : {
                    id : this.authService.getAppSession(),
                    expireIn : new Date((JSON.parse(atob(this.authService.getToken().split('.')[1])).exp) * 1000)
                },
                refreshSession : {
                    id : this.authService.getAppSession(),
                    expireIn : new Date((JSON.parse(atob(this.authService.getRefreshToken().split('.')[1])).exp) * 1000)
                },
                user : this.headerService.user,
            };
        }

        if (msg !== '') {
            console.log(msg, info);
        } else {
            console.log(info);
        }
    }
}
