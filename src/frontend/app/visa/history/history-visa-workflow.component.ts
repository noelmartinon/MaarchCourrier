import { Component, Input, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { FunctionsService } from '@service/functions.service';
import { tap, catchError, finalize } from 'rxjs/operators';
import { of } from 'rxjs';
import { MatDialog } from '@angular/material/dialog';


@Component({
    selector: 'app-history-visa-workflow',
    templateUrl: 'history-visa-workflow.component.html',
    styleUrls: ['history-visa-workflow.component.scss'],
})
export class HistoryVisaWorkflowComponent implements OnInit {

    visaWorkflowHistory: any[] = [];

    loading: boolean = false;
    data: any;

    @Input() resId: number = null;

    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        public functions: FunctionsService,
        public dialog: MatDialog,
    ) { }

    ngOnInit(): void {
        if (!this.functions.empty(this.resId)) {
            this.loadWorkflowHistory();
        }
        this.loading = false;
    }


    loadWorkflowHistory() {
        this.loading = true;
        this.visaWorkflowHistory = [
            {
                user: 'Bernard BLIER',
                creationDate: '2020-10-06 17:02:19.558904',
                'items': [
                    {
                        'listinstance_id': 101,
                        'sequence': 0,
                        'item_id': 7,
                        'item_type': 'user',
                        'item_firstname': 'Edith',
                        'item_lastname': 'ERINA',
                        'item_entity': 'Cabinet du Maire',
                        'viewed': 0,
                        'process_date': '2020-11-02 16:51:00.635663',
                        'process_comment': null,
                        'signatory': false,
                        'requested_signature': true,
                        'delegate': null,
                        'isValid': false,
                        'labelToDisplay': 'Edith ERINA',
                        'delegatedBy': null,
                        'hasPrivilege': true,
                        'difflist_type': 'VISA_CIRCUIT'
                    },
                    {
                        'listinstance_id': 102,
                        'sequence': 1,
                        'item_id': 26,
                        'item_type': 'user',
                        'item_firstname': 'test',
                        'item_lastname': 'test',
                        'item_entity': 'Service Courrier',
                        'viewed': 0,
                        'process_date': '2020-11-30 09:35:11.06508',
                        'process_comment': null,
                        'signatory': false,
                        'requested_signature': false,
                        'delegate': 21,
                        'isValid': true,
                        'labelToDisplay': 'Bernard BLIER',
                        'delegatedBy': 'test test',
                        'hasPrivilege': true,
                        'difflist_type': 'VISA_CIRCUIT'
                    },
                    {
                        'listinstance_id': 103,
                        'sequence': 2,
                        'item_id': 18,
                        'item_type': 'user',
                        'item_firstname': 'Denis',
                        'item_lastname': 'DAULL',
                        'item_entity': 'Secrétariat Général',
                        'viewed': 0,
                        'process_date': null,
                        'process_comment': null,
                        'signatory': false,
                        'requested_signature': false,
                        'delegate': null,
                        'isValid': true,
                        'labelToDisplay': 'Denis DAULL',
                        'delegatedBy': null,
                        'hasPrivilege': true,
                        'difflist_type': 'VISA_CIRCUIT'
                    },
                    {
                        'listinstance_id': 104,
                        'sequence': 3,
                        'item_id': 6,
                        'item_type': 'user',
                        'item_firstname': 'Jenny',
                        'item_lastname': 'JANE',
                        'item_entity': 'Centre Communal d\'Action Sociale',
                        'viewed': 0,
                        'process_date': null,
                        'process_comment': null,
                        'signatory': false,
                        'requested_signature': false,
                        'delegate': null,
                        'isValid': true,
                        'labelToDisplay': 'Jenny JANE',
                        'delegatedBy': null,
                        'hasPrivilege': true,
                        'difflist_type': 'VISA_CIRCUIT'
                    }
                ]
            }
        ];

        /* return new Promise((resolve, reject) => {
            this.http.get(`../rest/resources/${this.resId}/visaCircuitHistory`).pipe(
                tap((data: any) => {

                }),
                finalize(() => {
                    this.loading = false;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });*/
    }
}
