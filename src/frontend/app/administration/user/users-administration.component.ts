import { Component, OnInit, ViewChild, Inject, TemplateRef, ViewContainerRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { MatTableDataSource } from '@angular/material/table';
import { NotificationService } from '../../../service/notification/notification.service';
import { HeaderService } from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import { FunctionsService } from '../../../service/functions.service';

@Component({
    templateUrl: 'users-administration.component.html',
    styleUrls: ['users-administration.component.scss']
})
export class UsersAdministrationComponent implements OnInit {

    @ViewChild('adminMenuTemplate', { static: true }) adminMenuTemplate: TemplateRef<any>;

    dialogRef: MatDialogRef<any>;

    lang: any = LANG;
    loading: boolean = false;
    updateListModel: boolean = true;
    updateListInstance: boolean = true;

    data: any[] = [];
    config: any = {};
    userDestRedirect: any = {};
    userDestRedirectModels: any[] = [];
    listinstances: any[] = [];
    quota: any = {};
    user: any = {};
    withWebserviceAccount: boolean = false;
    webserviceAccounts: any[] = [];
    noWebserviceAccounts: any[] = [];

    dataSource = new MatTableDataSource(this.data);
    displayedColumns = ['id', 'user_id', 'lastname', 'firstname', 'status', 'mail', 'actions'];


    @ViewChild(MatPaginator, { static: false }) paginator: MatPaginator;
    @ViewChild(MatSort, { static: false }) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
        this.dataSource.filterPredicate = (template, filter: string) => {
            return this.functions.filterUnSensitive(template, filter, ['id', 'user_id', 'lastname', 'firstname', 'mail']);
        };
    }

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialog: MatDialog,
        private headerService: HeaderService,
        public appService: AppService,
        public functions: FunctionsService,
        private viewContainerRef: ViewContainerRef
    ) { }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.users);

        this.headerService.injectInSideBarLeft(this.adminMenuTemplate, this.viewContainerRef, 'adminMenu');

        this.user = this.headerService.user;
        this.loading = true;

        this.http.get('../rest/users')
            .subscribe((data: any) => {
                this.data = data['users'];
                this.data.forEach(element => {
                    element.statusLabel = this.lang['user' + element.status];
                    if (element.mode === 'rest') {
                        this.webserviceAccounts.push(element);
                    } else {
                        this.noWebserviceAccounts.push(element);
                    }
                });
                this.data = this.noWebserviceAccounts;
                this.quota = data['quota'];
                if (this.quota.actives > this.quota.userQuota) {
                    this.notify.error(this.lang.quotaExceeded);
                }

                this.loading = false;
                this.setDatasource();
            }, (err) => {
                this.notify.handleErrors(err);
            });
    }

    setDatasource() {
        setTimeout(() => {
            this.dataSource = new MatTableDataSource(this.data);
            this.dataSource.paginator = this.paginator;
            this.dataSource.sortingDataAccessor = this.functions.listSortingDataAccessor;
            this.sort.active = 'user_id';
            this.sort.direction = 'asc';
            this.dataSource.sort = this.sort;
        }, 0);
    }

    activateUser(user: any) {
        const r = confirm(this.lang.confirmAction + ' ' + this.lang.authorize + ' « ' + user.user_id + ' »');

        if (r) {
            user.status = 'OK';
            this.http.put('../rest/users/' + user.id, user)
                .subscribe(() => {
                    this.notify.success(this.lang.userAuthorized);
                    if (this.quota.userQuota) {
                        this.quota.inactives--;
                        this.quota.actives++;
                        if (this.quota.actives > this.quota.userQuota) {
                            this.notify.error(this.lang.quotaExceeded);
                        }
                    }
                }, (err) => {
                    user.status = 'SPD';
                    this.notify.error(err.error.errors);
                });
        }
    }

    deleteUser(user: any, mode: string) {
        user.mode = mode;

        this.http.get('../rest/users/' + user.id + '/isDeletable')
            .subscribe((response: any) => {
                if (response && response.hasOwnProperty('errors')) {
                    this.notify.error(response.errors);
                } else {
                    user.isDeletable = response.isDeletable;

                    if (response.isDeletable) {
                        this.config = {
                            panelClass: 'maarch-modal',
                            data: {
                                userDestRedirect: user,
                                isDeletable: response.isDeletable,
                                redirectListInstances: response.listInstances,
                                redirectListModels: response.listTemplates
                            }
                        };
                    } else {
                        this.config = {
                            panelClass: 'maarch-modal',
                            data: {
                                userDestRedirect: user,
                                isDeletable: response.isDeletable,
                                listInstanceEntities: response.listInstanceEntities,
                                listTemplateEntities: response.listTemplateEntities
                            }
                        };

                    }

                    // open modale
                    this.dialogRef = this.dialog.open(UsersAdministrationRedirectModalComponent, this.config);
                    this.dialogRef.afterClosed().subscribe((result: any) => {

                        if (result && user.isDeletable) {
                            user.inDiffListDest = result.inDiffListDest;
                            user.isResDestUser = result.isResDestUser;

                            if (result.inDiffListDest) {
                                user.redirectListModels = result.redirectListModels;
                            }

                            if (result.isResDestUser) {
                                user.redirectDestResUserId = result.redirectDestResUserId;

                                result.redirectListInstances.forEach((list: any) => {
                                    list.listInstances.forEach((element: any) => {
                                        if (element.item_mode === 'dest' && element.item_id === user.user_id) {
                                            element.item_id = user.redirectDestResUserId;
                                        }
                                    });
                                });

                                user.redirectListInstances = result.redirectListInstances;
                            }

                            if (user.inDiffListDest && user.isResDestUser) { // user is inDiffListDest and isResDestUser

                                // update listModels
                                this.http.put('../rest/listTemplates/entityDest/itemId/' + user.id, user)
                                    .subscribe(() => {
                                        this.http.put('../rest/listinstances', user.redirectListInstances)
                                            .subscribe((data: any) => {
                                                if (data != null && data.errors) {
                                                    this.notify.error(data.errors);
                                                } else {

                                                    // delete user
                                                    if (user.mode === 'delete') {
                                                        this.http.delete('../rest/users/' + user.id)
                                                            .subscribe(() => {
                                                                for (const i in this.data) {
                                                                    if (this.data[i].id == user.id) {
                                                                        this.data.splice(Number(i), 1);
                                                                    }
                                                                }
                                                                this.setDatasource();

                                                                if (this.quota.userQuota && user.status !== 'SPD') {
                                                                    this.quota.actives--;
                                                                } else if (this.quota.userQuota && user.status === 'SPD') {
                                                                    this.quota.inactives--;
                                                                }

                                                                this.notify.success(this.lang.userDeleted + ' « ' + user.user_id + ' »');

                                                                // end delete user
                                                            }, (err) => {
                                                                this.notify.error(err.error.errors);
                                                            });
                                                        // suspend user
                                                    } else if (user.mode === 'suspend') {
                                                        this.http.put('../rest/users/' + user.id + '/suspend', user)
                                                            .subscribe(() => {
                                                                user.status = 'SPD';
                                                                this.notify.success(this.lang.userSuspended);
                                                                if (this.quota.userQuota) {
                                                                    this.quota.inactives++;
                                                                    this.quota.actives--;
                                                                }

                                                            }, (err) => {
                                                                user.status = 'OK';
                                                                this.notify.error(err.error.errors);
                                                            });
                                                    }
                                                }
                                                // end update listInstances
                                            }, (err) => {
                                                this.notify.error(err.error.errors);
                                            });
                                    }, (err) => {
                                        this.notify.error(err.error.errors);
                                    });

                            } else if (user.inDiffListDest && !user.isResDestUser) { // user is inDiffListDest
                                // update listModels
                                this.http.put('../rest/listTemplates/entityDest/itemId/' + user.id, user)
                                    .subscribe(() => {
                                        // delete user
                                        if (user.mode === 'delete') {
                                            this.http.delete('../rest/users/' + user.id)
                                                .subscribe(() => {
                                                    for (const i in this.data) {
                                                        if (this.data[i].id == user.id) {
                                                            this.data.splice(Number(i), 1);
                                                        }
                                                    }
                                                    this.setDatasource();

                                                    if (this.quota.userQuota && user.status === 'OK') {
                                                        this.quota.actives--;
                                                    } else if (this.quota.userQuota && user.status === 'SPD') {
                                                        this.quota.inactives--;
                                                    }

                                                    this.notify.success(this.lang.userDeleted + ' « ' + user.user_id + ' »');

                                                    // end delete user
                                                }, (err) => {
                                                    this.notify.error(err.error.errors);
                                                });
                                            // suspend user
                                        } else if (user.mode === 'suspend') {
                                            this.http.put('../rest/users/' + user.id + '/suspend', user)
                                                .subscribe(() => {
                                                    user.status = 'SPD';
                                                    this.notify.success(this.lang.userSuspended);
                                                    if (this.quota.userQuota) {
                                                        this.quota.inactives++;
                                                        this.quota.actives--;
                                                    }

                                                }, (err) => {
                                                    user.status = 'OK';
                                                    this.notify.error(err.error.errors);
                                                });
                                        }
                                    }, (err) => {
                                        this.notify.error(err.error.errors);
                                    });

                            } else if (!user.inDiffListDest && user.isResDestUser) { // user isResDestUser
                                // update listInstances
                                this.http.put('../rest/listinstances', user.redirectListInstances)
                                    .subscribe((data: any) => {
                                        if (data && data.hasOwnProperty('errors')) {
                                            this.notify.error(data.errors);
                                        } else {

                                            // delete user
                                            if (user.mode === 'delete') {
                                                this.http.delete('../rest/users/' + user.id)
                                                    .subscribe(() => {
                                                        for (const i in this.data) {
                                                            if (this.data[i].id === user.id) {
                                                                this.data.splice(Number(i), 1);
                                                            }
                                                        }
                                                        this.setDatasource();

                                                        if (this.quota.userQuota && user.status === 'OK') {
                                                            this.quota.actives--;
                                                        } else if (this.quota.userQuota && user.status === 'SPD') {
                                                            this.quota.inactives--;
                                                        }

                                                        this.notify.success(this.lang.userDeleted + ' « ' + user.user_id + ' »');

                                                        // end delete user
                                                    }, (err) => {
                                                        this.notify.error(err.error.errors);
                                                    });
                                                // suspend user
                                            } else if (user.mode === 'suspend') {
                                                this.http.put('../rest/users/' + user.id + '/suspend', user)
                                                    .subscribe(() => {
                                                        user.status = 'SPD';
                                                        this.notify.success(this.lang.userSuspended);
                                                        if (this.quota.userQuota) {
                                                            this.quota.inactives++;
                                                            this.quota.actives--;
                                                        }

                                                    }, (err) => {
                                                        user.status = 'OK';
                                                        this.notify.error(err.error.errors);
                                                    });
                                            }
                                        }
                                        // end update listInstances
                                    }, (err) => {
                                        this.notify.error(err.error.errors);
                                    });

                            } else if (!user.inDiffListDest && !user.isResDestUser) { // user is not inDiffListDest and is not isResDestUser

                                // delete user
                                if (user.mode === 'delete') {
                                    this.http.delete('../rest/users/' + user.id)
                                        .subscribe(() => {
                                            for (const i in this.data) {
                                                if (this.data[i].id == user.id) {
                                                    this.data.splice(Number(i), 1);
                                                }
                                            }
                                            this.setDatasource();

                                            if (this.quota.userQuota && user.status === 'OK') {
                                                this.quota.actives--;
                                            } else if (this.quota.userQuota && user.status === 'SPD') {
                                                this.quota.inactives--;
                                            }

                                            this.notify.success(this.lang.userDeleted + ' « ' + user.user_id + ' »');

                                            // end delete user
                                        }, (err) => {
                                            this.notify.error(err.error.errors);
                                        });
                                    // suspend user
                                } else if (user.mode === 'suspend') {
                                    this.http.put('../rest/users/' + user.id + '/suspend', user)
                                        .subscribe(() => {
                                            user.status = 'SPD';
                                            this.notify.success(this.lang.userSuspended);
                                            if (this.quota.userQuota) {
                                                this.quota.inactives++;
                                                this.quota.actives--;
                                            }

                                        }, (err) => {
                                            user.status = 'OK';
                                            this.notify.error(err.error.errors);
                                        });
                                }
                            }
                        }

                        // close modale
                    });
                }
                // end isDeletable
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    toggleWebserviceAccount() {
        this.withWebserviceAccount = !this.withWebserviceAccount;
        if (this.withWebserviceAccount) {
            this.data = this.webserviceAccounts;
        } else {
            this.data = this.noWebserviceAccounts;
        }
        this.setDatasource();
    }

}
@Component({
    templateUrl: 'users-administration-redirect-modal.component.html',
    styleUrls: ['users-administration-redirect-modal.scss'],
})
export class UsersAdministrationRedirectModalComponent implements OnInit {
    lang: any = LANG;
    loadModel: boolean = false;
    loadInstance: boolean = false;
    modalTitle: string = this.lang.confirmAction;

    constructor(
        public http: HttpClient,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public dialogRef: MatDialogRef<UsersAdministrationRedirectModalComponent>,
        private notify: NotificationService) {
    }

    ngOnInit(): void {

        if (this.data.isDeletable) {
            // get listModel
            if (this.data.redirectListModels.length > 0) {
                this.data.inDiffListDest = true;
            }

            // get listInstances
            if (this.data.redirectListInstances.length > 0) {
                this.data.isResDestUser = true;
            }
        } else {
            if (this.data.userDestRedirect.mode === 'delete') {
                this.modalTitle = this.lang.unableToDelete;
            } else {
                this.modalTitle = this.lang.unableToSuspend;
            }
            // get listModel
            if (this.data.listTemplateEntities.length > 0) {
                this.data.inTemplateList = true;
            }

            // get listInstances
            if (this.data.listInstanceEntities.length > 0) {
                this.data.inInstanceList = true;
            }
        }
    }

    setRedirectUserListModels(index: number, user: any) {
        if (this.data.userDestRedirect.user_id != user.id) {
            this.data.redirectListModels[index].redirectUserId = user.id;
        } else {
            this.data.redirectListModels[index].redirectUserId = null;
            this.notify.error(this.lang.userUnauthorized);
        }

    }

    setRedirectUserRes(user: any) {
        if (this.data.userDestRedirect.user_id != user.id) {
            this.data.redirectDestResUserId = user.id;
        } else {
            this.data.redirectDestResUserId = null;
            this.notify.error(this.lang.userUnauthorized);
        }

    }

    sendFunction() {
        let valid = true;

        if (this.data.inDiffListDest) {
            this.data.redirectListModels.forEach((element: any) => {
                if (!element.redirectUserId) {
                    valid = false;
                }
            });
        }

        if (this.data.isResDestUser) {
            if (!this.data.redirectDestResUserId) {
                valid = false;
            }
        }

        return valid;
    }
}
