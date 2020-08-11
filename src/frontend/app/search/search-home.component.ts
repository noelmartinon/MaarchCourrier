import { Component, OnInit } from '@angular/core';
import { LANG } from '../translate.component';
import { TranslateService } from '@ngx-translate/core';

@Component({
    selector: 'search-home',
    templateUrl: 'search-home.component.html',
})
export class SearchHomeComponent implements OnInit {

    lang: any = LANG;

    constructor(private translate: TranslateService) {
    }

    ngOnInit(): void { }

}
