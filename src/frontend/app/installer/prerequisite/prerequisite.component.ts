import { Component, OnInit, ViewChildren, QueryList, ViewChild, ElementRef } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs/internal/operators/tap';
import { NotificationService } from '../../../service/notification/notification.service';
import { of } from 'rxjs/internal/observable/of';
import { catchError } from 'rxjs/internal/operators/catchError';
import { LANG } from '../../translate.component';
import { environment } from '../../../environments/environment';
import { MatTooltip } from '@angular/material/tooltip';

@Component({
    selector: 'app-prerequisite',
    templateUrl: './prerequisite.component.html',
    styleUrls: ['./prerequisite.component.scss']
})
export class PrerequisiteComponent implements OnInit {

    lang: any = LANG;

    stepFormGroup: FormGroup;

    prerequisites: any = {};

    packagesList: any = {
        general: [
            {
                label: 'phpVersionValid',
                required: true
            },
            {
                label: 'writable',
                required: true
            },
        ],
        tools: [
            {
                label: 'unoconv',
                required: true
            },
            {
                label: 'netcatOrNmap',
                required: true
            },
            {
                label: 'pgsql',
                required: true
            },
            {
                label: 'curl',
                required: true
            },
            {
                label: 'zip',
                required: true
            },
            {
                label: 'wkhtmlToPdf',
                required: true
            },
            {
                label: 'imagick',
                required: true
            },

        ],
        phpExtensions: [
            {
                label: 'fileinfo',
                required: true
            }, {
                label: 'pdoPgsql',
                required: true
            },
            {
                label: 'gd',
                required: true
            },
            {
                label: 'mbstring',
                required: true
            },
            {
                label: 'json',
                required: true
            },
            {
                label: 'gettext',
                required: true
            },
            {
                label: 'xml',
                required: true
            },
        ],
        phpConfiguration: [
            {
                label: 'errorReporting',
                required: true
            },
            {
                label: 'displayErrors',
                required: true
            }
        ],
    };

    docMaarchUrl: string = `https://docs.maarch.org/gitbook/html/MaarchCourrier/${environment.VERSION.split('.')[0] + '.' + environment.VERSION.split('.')[1]}/guat/guat_prerequisites/home.html`;

    @ViewChildren('packageItem') packageItem: QueryList<any>;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private _formBuilder: FormBuilder
    ) { }

    ngOnInit(): void {
        this.stepFormGroup = this._formBuilder.group({
            firstCtrl: ['', Validators.required]
        });
        this.getStepData();
    }

    getStepData() {
        this.http.get(`../rest/installer/prerequisites`).pipe(
            tap((data: any) => {
                this.prerequisites = data.prerequisites;
                Object.keys(this.packagesList).forEach(group => {
                    this.packagesList[group].forEach((item: any, key: number) => {
                        this.packagesList[group][key].state = this.prerequisites[this.packagesList[group][key].label] ? 'ok' : 'ko';
                        if (this.packagesList[group][key].label === 'phpVersionValid') {
                            this.lang.install_phpVersionValid_desc = `${this.lang.currentVersion} : ${this.prerequisites['phpVersion']}`;
                        }
                    });
                });
                this.stepFormGroup.controls['firstCtrl'].setValue(this.checkStep());
                this.stepFormGroup.controls['firstCtrl'].markAsUntouched();
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    initStep() {
        let i = 0;
        Object.keys(this.packagesList).forEach(group => {
            this.packagesList[group].forEach((item: any, key: number) => {
                if (this.packagesList[group][key].state === 'ko') {
                    this.packageItem.toArray().filter((itemKo: any) => itemKo._elementRef.nativeElement.id === this.packagesList[group][key].label)[0].toggle();
                }
                i++;
            });
        });
    }

    checkStep() {
        let state = 'success';
        Object.keys(this.packagesList).forEach((group: any) => {
            this.packagesList[group].forEach((item: any) => {
                if (item.state === 'ko') {
                    state = '';
                }
            });
        });
        return state;
    }

    isValidStep() {
        return this.stepFormGroup === undefined ? false : this.stepFormGroup.controls['firstCtrl'].value === 'success';
    }

    getFormGroup() {
        return this.stepFormGroup;
    }

    getInfoToInstall(): any[] {
        return [];
    }
}
