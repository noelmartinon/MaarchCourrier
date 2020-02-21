import { Component, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MatSidenav } from '@angular/material/sidenav';
import { HeaderService }        from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import { tap, catchError } from 'rxjs/operators';
import { of } from 'rxjs';
import { FunctionsService } from '../../../service/functions.service';
import { FormControl } from '@angular/forms';

declare function $j(selector: any): any;

@Component({
    templateUrl: "action-administration.component.html",
    providers: [NotificationService, AppService]
})
export class ActionAdministrationComponent implements OnInit {

    /*HEADER*/
    @ViewChild('snav', { static: true }) public  sidenavLeft   : MatSidenav;
    @ViewChild('snav2', { static: true }) public sidenavRight  : MatSidenav;

    lang: any = LANG;
    creationMode: boolean;
    action: any = {};
    statuses: any[] = [];
    actionPages: any[] = [];
    categoriesList: any[] = [];
    keywordsList: any[] = [];

    loading: boolean = false;
    availableCustomFields: Array<any> = [];
    customFieldsFormControl = new FormControl({ value: '', disabled: false });
    selectedFieldsValue: Array<any> = [];
    selectedFieldsId: Array<any> = [];
    selectedValue: any;
    arMode: any;

    constructor(
        public http: HttpClient, 
        private route: ActivatedRoute, 
        private router: Router, 
        private notify: NotificationService, 
        private headerService: HeaderService,
        public appService: AppService,
        public functions: FunctionsService) {
        $j("link[href='merged_css.php']").remove();
    }

    ngOnInit(): void {
        this.loading = true;

        this.route.params.subscribe(params => {
            if (typeof params['id'] == "undefined") {
                window['MainHeaderComponent'].setSnav(this.sidenavLeft);
                window['MainHeaderComponent'].setSnavRight(null);

                this.creationMode = true;

                this.http.get('../../rest/initAction')
                    .subscribe((data: any) => {
                        this.action = data.action;
                        this.categoriesList = data.categoriesList;
                        this.statuses = data.statuses;

                        this.actionPages = data['actionPages'];
                        this.keywordsList = data.keywordsList;
                        this.headerService.setHeader(this.lang.actionCreation);
                        this.loading = false;
                    });
            }
            else {
                window['MainHeaderComponent'].setSnav(this.sidenavLeft);
                window['MainHeaderComponent'].setSnavRight(null);
                
                this.creationMode = false;

                this.http.get('../../rest/actions/' + params['id'])
                    .subscribe(async (data: any) => {
                        this.action = data.action;
                        this.categoriesList = data.categoriesList;
                        this.statuses = data.statuses;
                        this.actionPages = data['actionPages'];
                        this.keywordsList = data.keywordsList;
                        this.headerService.setHeader(this.lang.actionCreation, data.action.label_action);
                        await this.getCustomFields();
                        this.loading = false;
                        this.customFieldsFormControl = new FormControl({ value: this.action.parameters, disabled: false });
                        if (this.action.actionPageId=='close_mail') {
                            this.selectedFieldsId = this.action.parameters;
                            this.selectedFieldsId.forEach((element: any) => {
                                this.availableCustomFields.forEach((availableElement: any) => {
                                    if (availableElement.id == element) {
                                        this.selectedFieldsValue.push(availableElement.label);
                                    }
                                });
                            });
                        } else if (this.action.actionPageId=='create_acknowledgement_receipt') {
                            this.arMode = this.action.parameters;
                        }
                    });
            }
        });
    }

    getCustomFields() {
        return new Promise((resolve, reject) => {
            if (this.action.actionPageId=='close_mail' && this.functions.empty(this.availableCustomFields)) {
                this.http.get('../../rest/customFields').pipe(
                    tap((data: any) => {
                        this.availableCustomFields = data.customFields.map((info: any) => {
                            info.id = 'indexingCustomField_' + info.id; 
                            return info;
                        });
                        return resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleSoftErrors(err);
                        return of(false);
                    })
                ).subscribe();
            } else {
                resolve(true);
            }
        });
    }

    getSelectedFields() {
        this.availableCustomFields.forEach((element: any) => {
            if (element.id == this.customFieldsFormControl.value) {
                this.selectedValue = element;
            }
        });
        if (this.selectedFieldsId.indexOf(this.customFieldsFormControl.value) < 0) {
            this.selectedFieldsValue.push(this.selectedValue.label);
            this.selectedFieldsId.push(this.customFieldsFormControl.value);
        }
        this.customFieldsFormControl.reset();
    }

    removeSelectedFields(index: number) {
        this.selectedFieldsValue.splice(index, 1);
        this.selectedFieldsId.splice(index, 1);
    }

    onSubmit() {
        if (this.action.actionPageId=='close_mail') {
            this.action.parameters = { requiredFields: this.selectedFieldsId};
        } else if (this.action.actionPageId=='create_acknowledgement_receipt') {
            this.action.parameters = { mode: this.arMode};
        }
        if (this.creationMode) {
            this.http.post('../../rest/actions', this.action)
                .subscribe(() => {
                    this.router.navigate(['/administration/actions']);
                    this.notify.success(this.lang.actionAdded);

                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.http.put('../../rest/actions/' + this.action.id, this.action)
                .subscribe(() => {
                    this.router.navigate(['/administration/actions']);
                    this.notify.success(this.lang.actionUpdated);

                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }
}
