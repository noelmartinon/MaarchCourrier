import { Component, OnInit, ViewChild } from '@angular/core';
import { FormGroup, FormBuilder, Validators, FormControl } from '@angular/forms';
import { TranslateService } from '@ngx-translate/core';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';
import { NotificationService } from '../../../../service/notification/notification.service';
import { HeaderService } from '../../../../service/header.service';
import { AppService } from '../../../../service/app.service';
import { MaarchFlatTreeComponent } from '../../../../plugins/tree/maarch-flat-tree.component';
import { map, tap, catchError, debounceTime, filter, distinctUntilChanged, switchMap } from 'rxjs/operators';
import { of } from 'rxjs/internal/observable/of';
import { Observable } from 'rxjs/internal/Observable';

@Component({
    selector: 'app-issuing-site',
    templateUrl: './issuing-site.component.html',
    styleUrls: ['./issuing-site.component.scss']
})
export class IssuingSiteComponent implements OnInit {

    creationMode: boolean;
    loading: boolean = true;

    adminFormGroup: FormGroup;
    entities: any = [];

    addressBANInfo: string = '';
    addressBANMode: boolean = true;
    addressBANControl = new FormControl();
    addressBANLoading: boolean = false;
    addressBANResult: any[] = [];
    addressBANFilteredResult: Observable<string[]>;
    addressBANCurrentDepartment: string = '75';
    departmentList: any[] = [];

    @ViewChild('maarchTree', { static: false }) maarchTree: MaarchFlatTreeComponent;

    constructor(
        private translate: TranslateService,
        public http: HttpClient,
        private route: ActivatedRoute,
        private router: Router,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        private _formBuilder: FormBuilder,
    ) { }

    ngOnInit(): void {
        this.route.params.subscribe((params) => {

            if (typeof params['id'] === 'undefined') {
                this.creationMode = true;

                this.headerService.setHeader(this.translate.instant('lang.issuingSiteCreation'));
                this.getEntities();
                this.initBanSearch();
                this.initAutocompleteAddressBan();
                this.adminFormGroup = this._formBuilder.group({
                    id: [null],
                    siteLabel: ['', Validators.required],
                    postOfficeLabel: ['', Validators.required],
                    accountNumber: ['', Validators.required],
                    addressName: [''],
                    addressNumber: [''],
                    addressStreet: [''],
                    addressAdditional1: [''],
                    addressadditional2: [''],
                    addressPostcode: [''],
                    addressTown: [''],
                    addressCountry: ['']
                });

                this.loading = false;
            } else {

                /*this.creationMode = false;
                this.http.get('../rest/parameters/' + params['id'])
                    .subscribe((data: any) => {
                        this.parameter = data.parameter;
                        this.headerService.setHeader(this.translate.instant('lang.issuingSiteModification'), this.parameter.id);
                        if (typeof (this.parameter.param_value_int) === 'number') {
                            this.type = 'int';
                        } else if (this.parameter.param_value_date) {
                            this.type = 'date';
                        } else {
                            this.type = 'string';
                        }

                        this.loading = false;
                    }, (err) => {
                        this.notify.handleErrors(err);
                    });*/
            }
        });
    }

    initBanSearch() {
        this.http.get('../rest/ban/availableDepartments').pipe(
            tap((data: any) => {
                if (data.default !== null && data.departments.indexOf(data.default.toString()) !== - 1) {
                    this.addressBANCurrentDepartment = data.default;
                }
                this.departmentList = data.departments;
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    initAutocompleteAddressBan() {
        this.addressBANInfo = this.translate.instant('lang.autocompleteInfo');
        this.addressBANResult = [];
        this.addressBANControl.valueChanges
            .pipe(
                debounceTime(300),
                filter(value => value.length > 2),
                distinctUntilChanged(),
                tap(() => this.addressBANLoading = true),
                switchMap((data: any) => this.http.get('../rest/autocomplete/banAddresses', { params: { 'address': data, 'department': this.addressBANCurrentDepartment } })),
                tap((data: any) => {
                    if (data.length === 0) {
                        this.addressBANInfo = this.translate.instant('lang.noAvailableValue');
                    } else {
                        this.addressBANInfo = '';
                    }
                    this.addressBANResult = data;
                    this.addressBANFilteredResult = of(this.addressBANResult);
                    this.addressBANLoading = false;
                })
            ).subscribe();
    }

    resetAutocompleteAddressBan() {
        this.addressBANResult = [];
        this.addressBANInfo = this.translate.instant('lang.autocompleteInfo');
    }

    selectAddressBan(ev: any) {
        this.adminFormGroup.controls['addressNumber'].setValue(ev.option.value.number);
        this.adminFormGroup.controls['addressStreet'].setValue(ev.option.value.afnorName);
        this.adminFormGroup.controls['addressPostcode'].setValue(ev.option.value.postalCode);
        this.adminFormGroup.controls['addressTown'].setValue(ev.option.value.city);
        this.adminFormGroup.controls['addressCountry'].setValue('FRANCE');
        this.addressBANControl.setValue('');
    }

    getEntities() {
        this.http.get(`../rest/entities`).pipe(
            map((data: any) => {
                data.entities = data.entities.map((entity: any) => {
                    return {
                        text: entity.entity_label,
                        icon: entity.icon,
                        parent_id: entity.parentSerialId,
                        id: entity.serialId,
                        state: {
                            opened: true
                        }
                    };
                });
                return data.entities;
            }),
            tap((entities: any) => {
                this.entities = entities;
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    onSubmit() {
        const objToSubmit = {};
        Object.keys(this.adminFormGroup.controls).forEach(key => {
            objToSubmit[key] = this.adminFormGroup.controls[key].value;
        });

        objToSubmit['entities'] = this.maarchTree.getSelectedNodes().map((ent: any) => ent.id);

        console.log(objToSubmit);

        if (this.creationMode) {
            this.http.post('../rest/recommended/sites', objToSubmit)
                .subscribe(() => {
                    this.notify.success(this.translate.instant('lang.priorityAdded'));
                    this.router.navigate(['/administration/issuingSite']);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            /*this.http.put('../rest/recommended/sites/' + this.id, objToSubmit)
                .subscribe(() => {
                    this.notify.success(this.translate.instant('lang.priorityUpdated'));
                    this.router.navigate(['/administration/priorities']);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });*/
        }
    }
}
