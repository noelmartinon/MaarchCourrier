import { Component, OnInit, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { AppService } from '../../../service/app.service';
import { HeaderService } from '../../../service/header.service';
import { NotificationService } from '../../../service/notification/notification.service';
import { MatSidenav } from '@angular/material/sidenav';
import { FunctionsService } from '../../../service/functions.service';
import { ActivatedRoute, Router } from '@angular/router';
import { tap } from 'rxjs/internal/operators/tap';
import { catchError } from 'rxjs/internal/operators/catchError';
import { of } from 'rxjs/internal/observable/of';
import { map } from 'rxjs/internal/operators/map';

@Component({
    selector: 'app-alfresco',
    templateUrl: './alfresco-administration.component.html',
    styleUrls: ['./alfresco-administration.component.scss']
})
export class AlfrescoAdministrationComponent implements OnInit {

    @ViewChild('snav2', { static: true }) public sidenavRight: MatSidenav;

    lang: any = LANG;
    loading: boolean = false;
    creationMode: boolean = true;

    entities: any[] = [];
    availableEntities: any[] = [];

    alfresco: any = {
        id: 0,
        label: '',
        account: {
            id: '',
            password: '',
        },
        rootFolder: null,
        linkedEntities: []
    };

    hidePassword: boolean = true;
    alfrescoTreeLoaded: boolean = false;

    constructor(
        public http: HttpClient,
        private route: ActivatedRoute,
        private router: Router,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public functionsService: FunctionsService
    ) { }

    ngOnInit() {
        this.loading = false;
        this.route.params.subscribe(async params => {
            if (typeof params['id'] === 'undefined') {
                this.headerService.setHeader(this.lang.alfrescoCreation);
                this.creationMode = true;
            } else {
                this.headerService.setHeader(this.lang.alfrescoModification);

                this.alfresco.id = params['id'];
                this.creationMode = false;
            }
            await this.getEntities();
            await this.getAvailableEntities();
            await this.initAccount();
            this.loading = false;
        });
    }

    onSubmit() {
        if (this.creationMode) {
            this.createAccount();
        } else {
            this.updateAccount();
        }
    }

    createAccount() {
        this.http.post('../rest/alfresco/accounts', this.formatData()).pipe(
            tap(() => {
                this.notify.success(this.lang.accountAdded);
                this.router.navigate(['/administration/alfresco']);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    updateAccount() {
        this.http.put(`../rest/alfresco/accounts/${this.alfresco.id}`, this.formatData()).pipe(
            tap(() => {
                this.notify.success(this.lang.accountUpdated);
                this.router.navigate(['/administration/alfresco']);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    formatData() {
        const alfresco: any = {
            label: this.alfresco.label,
            login: this.alfresco.account.id,
            nodeId: this.alfresco.rootFolder,
            entities: $('#jstree').jstree('get_checked', null, true)
        };

        if (!this.functionsService.empty(this.alfresco.account.password)) {
            alfresco.password = this.alfresco.account.password;
        }

        return alfresco;
    }

    getAvailableEntities() {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/alfresco/availableEntities`).pipe(
                tap((data: any) => {
                    this.availableEntities = data['availableEntities'];
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    getEntities() {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/entities`).pipe(
                map((data: any) => {
                    data.entities = data.entities.map((entity: any) => {
                        return {
                            text: entity.entity_label,
                            icon: entity.icon,
                            parent: entity.parentSerialId,
                            id: entity.serialId.toString(),
                            state: {
                                opened: true
                            }
                        };
                    });
                    return data.entities;
                }),
                tap((entities: any) => {
                    console.log(entities);

                    this.entities = entities;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    initAccount() {
        return new Promise((resolve, reject) => {
            if (this.creationMode) {
                this.http.get('../rest/entities').pipe(
                    map((data: any) => {
                        data.entities = data.entities.map((entity: any) => {
                            return {
                                text: entity.entity_label,
                                icon: entity.icon,
                                parent: entity.parentSerialId,
                                id: entity.serialId.toString(),
                                state: {
                                    opened: true
                                }
                            };
                        });
                        return data.entities;
                    }),
                    tap((entities: any) => {
                        this.entities = entities;

                        this.entities.forEach(element => {
                            if (this.availableEntities.indexOf(+element.id) > -1) {
                                element.state.disabled = false;
                            } else {
                                element.state.disabled = true;
                            }
                        });

                        setTimeout(() => {
                            this.initEntitiesTree(this.entities);
                        }, 0);
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleErrors(err);
                        return of(false);
                    })
                ).subscribe();

            } else {
                this.http.get(`../rest/alfresco/accounts/${this.alfresco.id}`).pipe(
                    tap((data: any) => {
                        this.alfresco = {
                            id: data.id,
                            label: data.label,
                            account: {
                                id: data.login
                            },
                            rootFolder: data.nodeId,
                            linkedEntities: data.entities
                        };

                        this.entities.forEach(element => {
                            if (this.availableEntities.indexOf(+element.id) > -1) {
                                element.state.disabled = false;
                            } else {
                                element.state.disabled = true;
                            }
                            if (this.alfresco.linkedEntities.indexOf(+element.id) > -1) {
                                element.state.disabled = false;
                                element.state.selected = true;
                            }
                        });
                        setTimeout(() => {
                            this.initEntitiesTree(this.entities);
                        }, 0);
                        resolve(true);
                    }),
                    catchError((err: any) => {
                        this.notify.handleErrors(err);
                        return of(false);
                    })
                ).subscribe();
            }

        });
    }

    initEntitiesTree(entities: any) {
        $('#jstree')
            .jstree({
                'checkbox': {
                    'three_state': false // no cascade selection
                },
                'core': {
                    force_text: true,
                    'themes': {
                        'name': 'proton',
                        'responsive': true
                    },
                    'data': entities
                },
                'plugins': ['checkbox', 'search', 'sort']
            });
        let to: any = false;
        $('#jstree_search').keyup(function () {
            if (to) { clearTimeout(to); }
            to = setTimeout(function () {
                const v: any = $('#jstree_search').val();
                $('#jstree').jstree(true).search(v);
            }, 250);
        });
    }

    validAccount() {
        if (this.functionsService.empty(this.alfresco.rootFolder) || $('#jstree').jstree('get_checked', null, true).length === 0) {
            return false;
        } else {
            return true;
        }
    }

    checkAccount() {
        let alfresco  = {};
        if (!this.creationMode) {
            alfresco = {
                accountId : this.alfresco.id,
                login: this.alfresco.account.id,
                password: this.alfresco.account.password,
                nodeId : this.alfresco.rootFolder
            };
        } else {
            alfresco = {
                login: this.alfresco.account.id,
                password: this.alfresco.account.password,
                nodeId : this.alfresco.rootFolder
            };
        }

        this.http.post(`../rest/alfresco/checkAccounts`, alfresco).pipe(
            tap(() => {
                this.notify.success(this.lang.testSucceeded);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
