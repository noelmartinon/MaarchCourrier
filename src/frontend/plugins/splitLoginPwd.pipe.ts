import { Pipe, PipeTransform } from '@angular/core';
import { LatinisePipe } from 'ngx-pipes';

@Pipe({
    name: 'splitLoginPwd'
})
export class SplitLoginPwd implements PipeTransform {

    constructor(private latinisePipe: LatinisePipe) { }

    transform(url: string): string {
        if (url.indexOf('@') > -1) {
            const protocole: string = url.substring(0, url.indexOf('://'));
            const serverName: string= url.substring(url.indexOf('@') + 1, url.length)
            const URL: string = protocole.concat('://').concat(serverName);
            return URL;
        } else {
            return url;
        }
    }
}
