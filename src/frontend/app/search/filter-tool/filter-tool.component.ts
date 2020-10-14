import { Component, OnInit, ViewChild, ElementRef, EventEmitter, Output, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '@service/app.service';
import { FunctionsService } from '@service/functions.service';

@Component({
    selector: 'app-filter-tool-adv-search',
    templateUrl: 'filter-tool.component.html',
    styleUrls: ['filter-tool.component.scss']
})
export class FilterToolComponent implements OnInit {

    @Input() filters: any = {};
    @Input() isLoadingResults: boolean = false;

    @Output() filterChanged = new EventEmitter<any>();

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        public functions: FunctionsService) { }

    ngOnInit(): void { }

    setfilters(filters: any) {
        console.log(filters);
        this.filters = filters;
    }

    toggleFilter(key: string, index: number) {
        this.filters[key][index].selected = !this.filters[key][index].selected;
        this.filterChanged.emit();
    }
}
