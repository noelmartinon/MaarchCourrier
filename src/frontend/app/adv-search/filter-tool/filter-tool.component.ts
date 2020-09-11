import { Component, OnInit, ViewChild, ElementRef, EventEmitter, Output, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';
import { Observable } from 'rxjs';
import { FormControl } from '@angular/forms';
import { startWith, map } from 'rxjs/operators';
import { LatinisePipe } from 'ngx-pipes';
import { MatExpansionPanel } from '@angular/material/expansion';

@Component({
    selector: 'app-filter-tool-adv-search',
    templateUrl: 'filter-tool.component.html',
    styleUrls: ['filter-tool.component.scss']
})
export class FilterToolComponent implements OnInit {

    filters = {
        categories: [],
        priorities: [],
        statuses: [],
        doctypes: [],
        destinations: [],
        folders: []
    };
    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        public appService: AppService,
        public functions: FunctionsService,
        private latinisePipe: LatinisePipe) { }

    ngOnInit(): void {

        // FOR TEST
        this.filters = {
            categories: [
                {
                    id: 'outgoing',
                    label: 'Courrier départ',
                    resCount: 24,
                    selected: true,
                },
                {
                    id: 'incoming',
                    label: 'Courrier arrivé',
                    resCount: 4,
                    selected: true,
                }
            ],
            priorities: [
                {
                    id: 'IUDZIZOJD',
                    label : 'Normal',
                    resCount: 4,
                    selected: true,
                },
                {
                    id: 'IUDZIZOJD',
                    label : 'Urgent',
                    resCount: 42,
                    selected: true,
                }
            ],
            statuses: [
                {
                    id: 'IUDZIZOJD',
                    label : 'En cours',
                    resCount: 42,
                    selected: true,
                }
            ],
            doctypes: [
                {
                    id: 'IUDZIZOJD',
                    label : 'Convocations',
                    resCount: 42,
                    selected: true,
                },
                {
                    id: 'IUDZIZOJD',
                    label : 'Litiges',
                    resCount: 42,
                    selected: true,
                }
            ],
            destinations: [
                {
                    id: 'IUDZIZOJD',
                    label : 'Pôle Jeunesse et Sport',
                    resCount: 42,
                    selected: true,
                },
                {
                    id: 'IUDZIZOJD',
                    label : 'Pôle culturel',
                    resCount: 42,
                    selected: true,
                }
            ],
            folders: [

            ]
        };
    }

}
