import { Component, OnInit, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { LANG } from '../../translate.component';
import { TranslateService } from '@ngx-translate/core';
import { MatSidenav } from '@angular/material/sidenav';
import { HeaderService } from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import { PrivilegeService } from '../../../service/privileges.service';
import { Observable, of } from 'rxjs';
import { FormControl } from '@angular/forms';
import { startWith, map, tap, catchError, exhaustMap } from 'rxjs/operators';
import { LatinisePipe } from 'ngx-pipes';
import { NotificationService } from '../../../service/notification/notification.service';
import { FunctionsService } from '../../../service/functions.service';
import { FeatureTourService } from '../../../service/featureTour.service';

@Component({
    templateUrl: 'administration.component.html',
    styleUrls: ['administration.component.scss']
})
export class AdministrationComponent implements OnInit, AfterViewInit {

    lang: any = LANG;
    loading: boolean = false;

    shortcutsAdmin: any[] = [];
    organisationServices: any[] = [];
    productionServices: any[] = [];
    classementServices: any[] = [];
    supervisionServices: any[] = [];

    searchService = new FormControl();

    administrations: any[] = [];
    filteredAdministrations: Observable<string[]>;

    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;
    @ViewChild('searchServiceInput', { static: true }) searchServiceInput: ElementRef;

    constructor(
        private translate: TranslateService,
        public http: HttpClient,
        private router: Router,
        private headerService: HeaderService,
        public appService: AppService,
        private privilegeService: PrivilegeService,
        private latinisePipe: LatinisePipe,
        private notify: NotificationService,
        private functionService: FunctionsService,
        private featureTourService: FeatureTourService
    ) { }

    ngOnInit(): void {
        this.headerService.setHeader(this.translate.instant('lang.administration'));

        // this.loading = true;

        this.organisationServices = this.privilegeService.getCurrentUserAdministrationsByUnit('organisation');
        this.productionServices = this.privilegeService.getCurrentUserAdministrationsByUnit('production');
        this.classementServices = this.privilegeService.getCurrentUserAdministrationsByUnit('classement');
        this.supervisionServices = this.privilegeService.getCurrentUserAdministrationsByUnit('supervision');

        this.administrations = this.organisationServices.concat(this.productionServices).concat(this.classementServices).concat(this.supervisionServices);

        this.shortcutsAdmin = this.administrations.filter(admin => ['admin_users', 'admin_groups', 'manage_entities'].indexOf(admin.id) > -1).map(admin => {
            return {
                ...admin,
                count: 0
            };
        });

        this.getNbShortcuts();

        this.filteredAdministrations = this.searchService.valueChanges
            .pipe(
                startWith(''),
                map(value => this._filter(value, 'administrations'))
            );

        this.loading = false;

        setTimeout(() => {
            this.searchServiceInput.nativeElement.focus();
        }, 0);
    }

    ngAfterViewInit(): void {
        if (this.headerService.user.mode === 'root_visible' || this.headerService.user.mode === 'root_invisible') {
            this.featureTourService.init();
        }
    }

    goToSpecifiedAdministration(service: any): void {
        if (service.angular === true) {
            this.router.navigate([service.route]);
        } else {
            window.location.assign(service.route);
        }
    }

    private _filter(value: string, type: string): string[] {
        if (typeof value === 'string') {
            const filterValue = this.latinisePipe.transform(value.toLowerCase());
            return this[type].filter((option: any) => this.latinisePipe.transform(option['label'].toLowerCase()).includes(filterValue));
        } else {
            return this[type];
        }
    }

    getNbShortcuts() {
        this.http.get('../rest/administration/details').pipe(
            tap((data: any) => {
                if (!this.functionService.empty(data.count.users)) {
                    this.shortcutsAdmin.filter(admin => admin.id === 'admin_users')[0].count = data.count.users;
                }
                if (!this.functionService.empty(data.count.groups)) {
                    this.shortcutsAdmin.filter(admin => admin.id === 'admin_groups')[0].count = data.count.groups;
                }
                if (!this.functionService.empty(data.count.entities)) {
                    this.shortcutsAdmin.filter(admin => admin.id === 'manage_entities')[0].count = data.count.entities;
                }
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
