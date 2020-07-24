import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { NotificationService } from '../../../service/notification/notification.service';
import { LANG } from '../../translate.component';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { tap } from 'rxjs/internal/operators/tap';
import { of } from 'rxjs/internal/observable/of';
import { catchError } from 'rxjs/internal/operators/catchError';
import { environment } from '../../../environments/environment';


@Component({
    selector: 'app-welcome',
    templateUrl: './welcome.component.html',
    styleUrls: ['./welcome.component.scss']
})
export class WelcomeComponent implements OnInit {

    lang: any = LANG;

    stepFormGroup: FormGroup;

    langs: string[] = [];

    appVersion: string = environment.VERSION.split('.')[0] + '.' + environment.VERSION.split('.')[1];

    steps: any[] = [
        {
            icon : 'fas fa-check-square',
            desc: this.lang.prerequisiteCheck
        },
        {
            icon : 'fa fa-database',
            desc: this.lang.databaseCreation
        },
        {
            icon : 'fa fa-database',
            desc: this.lang.dataSampleCreation
        },
        {
            icon : 'fa fa-hdd',
            desc: this.lang.docserverCreation
        },
        {
            icon : 'fas fa-tools',
            desc: this.lang.stepCustomizationActionDesc
        },
        {
            icon : 'fa fa-user',
            desc: this.lang.adminUserCreation
        },
    ];

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        private _formBuilder: FormBuilder
    ) { }

    ngOnInit(): void {
        this.stepFormGroup = this._formBuilder.group({
            lang: ['fr', Validators.required]
        });

        this.getLang();
    }

    getLang() {
        this.langs = [
            'fr',
            'en',
        ];
        /*this.http.get('../rest/dev/lang').pipe(
            tap((data: any) => {
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();*/
    }

    initStep() {
        return false;
    }

    getInfoToInstall(): any[] {
        return [];
        /*return [{
            idStep : 'lang',
            body: {
                lang: this.stepFormGroup.controls['lang'].value,
            },
            route : {
                method : 'POST',
                url : '../rest/installer/lang'
            },
            description: this.lang.langSetting,
            installPriority: 3
        }];*/
    }

}