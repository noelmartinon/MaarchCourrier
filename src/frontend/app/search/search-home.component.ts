import { Component, OnInit } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';

@Component({
    selector: 'search-home',
    templateUrl: 'search-home.component.html',
})
export class SearchHomeComponent implements OnInit {

    

    constructor(public translate: TranslateService) {
    }

    ngOnInit(): void { }

}
