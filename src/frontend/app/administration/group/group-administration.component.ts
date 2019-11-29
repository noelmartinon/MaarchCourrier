import { ChangeDetectorRef, Component, OnInit, ViewChild } from '@angular/core';
import { MediaMatcher } from '@angular/cdk/layout';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { MatPaginator, MatTableDataSource, MatSort, MatSidenav, MatDialog } from '@angular/material';

import { AutoCompletePlugin } from '../../../plugins/autocomplete.plugin';
import { map, tap, exhaustMap, finalize, catchError, filter } from 'rxjs/operators';
import { of } from 'rxjs';
import { ConfirmModalComponent } from '../../confirmModal.component';

declare function $j(selector: any): any;
declare const angularGlobals: any;


@Component({
    templateUrl: "group-administration.component.html",
    styleUrls: ['group-administration.component.scss'],
    providers: [NotificationService]
})
export class GroupAdministrationComponent extends AutoCompletePlugin implements OnInit {
    /*HEADER*/
    @ViewChild('snav') public sidenavLeft: MatSidenav;
    @ViewChild('snav2') public sidenavRight: MatSidenav;

    private _mobileQueryListener: () => void;
    mobileQuery: MediaQueryList;

    coreUrl: string;
    lang: any = LANG;
    loading: boolean = false;
    paramsLoading: boolean = false;

    group: any = {
        security: {}
    };
    creationMode: boolean;

    usersDisplayedColumns = ['firstname', 'lastname'];
    basketsDisplayedColumns = ['basket_name', 'basket_desc'];
    usersDataSource: any;
    basketsDataSource: any;

    authorizedGroupsUserParams: any[] = [];
    panelMode = 'keywordInfos';

    @ViewChild('paginatorBaskets') paginatorBaskets: MatPaginator;
    @ViewChild('sortBaskets') sortBaskets: MatSort;
    @ViewChild(MatPaginator) paginator: MatPaginator;
    @ViewChild('sortUsers') sortUsers: MatSort;

    applyFilter(filterValue: string) {
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.usersDataSource.filter = filterValue;
    }
    applyBasketsFilter(filterValue: string) {
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.basketsDataSource.filter = filterValue;
    }

    constructor(changeDetectorRef: ChangeDetectorRef, media: MediaMatcher, public http: HttpClient, private route: ActivatedRoute, private router: Router, private notify: NotificationService, private headerService: HeaderService, private dialog: MatDialog) {
        super(http, ['adminUsers']);
        $j("link[href='merged_css.php']").remove();
        this.mobileQuery = media.matchMedia('(max-width: 768px)');
        this._mobileQueryListener = () => changeDetectorRef.detectChanges();
        this.mobileQuery.addListener(this._mobileQueryListener);
    }

    ngOnDestroy(): void {
        this.mobileQuery.removeListener(this._mobileQueryListener);
    }

    ngOnInit(): void {
        this.coreUrl = angularGlobals.coreUrl;
        this.loading = true;

        this.route.params.subscribe(params => {
            if (typeof params['id'] == "undefined") {
                this.headerService.setHeader(this.lang.groupCreation);

                window['MainHeaderComponent'].setSnav(this.sidenavLeft);
                window['MainHeaderComponent'].setSnavRight(null);

                this.creationMode = true;
                this.loading = false;
            } else {
                window['MainHeaderComponent'].setSnav(this.sidenavLeft);
                window['MainHeaderComponent'].setSnavRight(null);

                this.creationMode = false;
                this.http.get(this.coreUrl + "rest/groups/" + params['id'] + "/details")
                    .subscribe((data: any) => {
                        this.group = data['group'];

                        // DEV SGAMI
                        let priv = '';
                        if (this.group.services.use[0].filter((priv: any) => priv.id === 'manage_personal_data' && priv.checked)[0]) {
                            priv = 'manage_personal_data';
                        } else if (this.group.services.use[0].filter((priv: any) => priv.id === 'view_personal_data' && priv.checked)[0]) {
                            priv = 'view_personal_data';
                        }

                        this.group.services.use[0] = this.group.services.use[0].filter((priv: any) => ['manage_personal_data', 'view_personal_data'].indexOf(priv.id) === -1)
                        const service = {
                            "id": "confidentialityAndSecurity_personal_data",
                            "name": this.lang.personalDataMsg,
                            "current": priv,
                            "services": [{
                                'id': 'view_personal_data',
                                'label': this.lang.viewPersonalData
                            },
                            {
                                'id': 'manage_personal_data',
                                'label': this.lang.managePersonalData
                            }]
                        };

                        this.group.services.use[0].push(service);

                        this.headerService.setHeader(this.lang.groupModification, this.group['group_desc']);
                        this.loading = false;
                        setTimeout(() => {
                            this.usersDataSource = new MatTableDataSource(this.group.users);
                            this.usersDataSource.paginator = this.paginator;
                            this.usersDataSource.sort = this.sortUsers;
                            this.basketsDataSource = new MatTableDataSource(this.group.baskets);
                            this.basketsDataSource.paginator = this.paginatorBaskets;
                            this.basketsDataSource.sort = this.sortBaskets;
                        }, 0);

                    }, () => {
                        location.href = "index.php";
                    });
            }
        });
    }

    onSubmit() {
        if (this.creationMode) {
            this.http.post(this.coreUrl + "rest/groups", this.group)
                .subscribe((data: any) => {
                    this.notify.success(this.lang.groupAdded);
                    this.router.navigate(["/administration/groups/" + data.group]);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.http.put(this.coreUrl + "rest/groups/" + this.group['id'], { "description": this.group['group_desc'], "security": this.group['security'] })
                .subscribe(() => {
                    this.notify.success(this.lang.groupUpdated);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    changePersonalDataPrivilege(ev: any) {

        if (ev.value === 'view_personal_data') {
            this.updateService({ id: 'view_personal_data', 'checked': true });
            this.updateService({ id: 'manage_personal_data', 'checked': false });

        } else if (ev.value === 'manage_personal_data') {
            this.updateService({ id: 'view_personal_data', 'checked': true });
            this.updateService({ id: 'manage_personal_data', 'checked': true });

        } else {
            this.updateService({ id: 'view_personal_data', 'checked': false });
            this.updateService({ id: 'manage_personal_data', 'checked': false });
        }

    }

    updateService(service: any) {
        if (service.checked) {
            this.sidenavRight.close();
        }

        if (service.checked && service.id === 'admin_groups') {
            const config = { data: { msg: this.lang.enableGroupMsg } };
            let dialogRef = this.dialog.open(ConfirmModalComponent, config);
            dialogRef.afterClosed().pipe(
                tap((data: string) => {
                    if (data !== 'ok') {
                        service.checked = false;
                    }
                }),
                filter((data: string) => data === 'ok'),
                tap(() => {
                    this.http.put(this.coreUrl + "rest/groups/" + this.group['id'] + "/services/" + service['id'], service)
                    .subscribe(() => {
                        this.notify.success(this.lang.groupServicesUpdated);
                    }, (err) => {
                        service.checked = !service.checked;
                        this.notify.error(err.error.errors);
                    });
                }),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();

        } else {
            this.http.put(this.coreUrl + "rest/groups/" + this.group['id'] + "/services/" + service['id'], service)
                .subscribe(() => {
                    this.notify.success(this.lang.groupServicesUpdated);
                }, (err) => {
                    service.checked = !service.checked;
                    this.notify.error(err.error.errors);
                });
        }
    }

    linkUser(newUser: any) {
        this.userCtrl.setValue('');
        $j('.autocompleteSearch').blur();
        var groupReq = {
            "groupId": this.group.group_id,
            "role": this.group.role
        };
        this.http.post(this.coreUrl + "rest/users/" + newUser.id + "/groups", groupReq)
            .subscribe(() => {
                var displayName = newUser.idToDisplay.split(" ");
                var user = {
                    id: newUser.id,
                    user_id: newUser.otherInfo,
                    firstname: displayName[0],
                    lastname: displayName[1]
                };
                this.group.users.push(user);
                this.usersDataSource = new MatTableDataSource(this.group.users);
                this.usersDataSource.paginator = this.paginator;
                this.usersDataSource.sort = this.sortUsers;
                this.notify.success(this.lang.userAdded);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    openUserParams(id: string) {
        this.sidenavRight.toggle();
        if (!this.sidenavRight.opened) {
            this.panelMode = '';
        } else {
            this.panelMode = id;
            this.paramsLoading = true;
            this.http.get(`../../rest/groups`).pipe(
                map((data: any) => {
                    data.groups = data.groups.map((group: any) => {
                        return {
                            id: group.id,
                            label: group.group_desc
                        }
                    });
                    return data;
                }),
                tap((data: any) => {
                    this.authorizedGroupsUserParams = data.groups;
                }),
                exhaustMap(() => this.http.get(`../../rest/groups/${this.group.id}/privileges/${this.panelMode}/parameters?parameter=groups`)),
                tap((data: any) => {
                    const allowedGroups: any[] = data;
                    this.authorizedGroupsUserParams.forEach(group => {
                        if (allowedGroups.indexOf(group.id) > -1) {
                            group.checked = true;
                        } else {
                            group.checked = false;
                        }
                    });
                }),
                finalize(() => this.paramsLoading = false),
                catchError((err: any) => {
                    this.notify.handleErrors(err);
                    return of(false);
                })
            ).subscribe();
        }
    }

    updatePrivilegeParams(paramList: any) {
        let obj = {};
        if (this.panelMode === 'admin_users') {
            obj = {
                groups: paramList.map((param: any) => param.value)
            }
        }
        this.http.put(`../../rest/groups/${this.group.id}/privileges/${this.panelMode}/parameters`, { parameters: obj }).pipe(
            tap(() => {
                this.notify.success('parametres modifiés');
            }),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }
}
