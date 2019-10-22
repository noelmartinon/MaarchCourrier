import { ChangeDetectorRef, Component, OnInit, ViewChild, Inject } from '@angular/core';
import { MediaMatcher } from '@angular/cdk/layout';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { MatPaginator, MatTableDataSource, MatSort, MatDialog, MatDialogRef, MAT_DIALOG_DATA, MatSidenav } from '@angular/material';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { AutoCompletePlugin } from '../../../plugins/autocomplete.plugin';

declare function $j(selector: any): any;

declare var angularGlobals: any;

@Component({
    templateUrl: "users-administration.component.html",
    styleUrls: ['users-administration.component.css'],
    providers: [NotificationService]
})
export class UsersAdministrationComponent extends AutoCompletePlugin implements OnInit {

    /*RESPONSIVE*/
    private _mobileQueryListener            : () => void;
    mobileQuery                             : MediaQueryList;

    @ViewChild('snav') public sidenavLeft   : MatSidenav;
    @ViewChild('snav2') public sidenavRight : MatSidenav;

    dialogRef                               : MatDialogRef<any>;

    coreUrl                                 : string;
    lang                                    : any                   = LANG;
    loading                                 : boolean               = false;
    updateListModel                         : boolean               = true;
    updateListInstance                      : boolean               = true;

    data                                    : any[]                 = [];
    config                                  : any                   = {};
    userDestRedirect                        : any                   = {};
    userDestRedirectModels                  : any[]                 = [];
    listinstances                           : any[]                 = [];
    quota                                   : any                   = {};
    user                                    : any                   = {};

    dataSource          = new MatTableDataSource(this.data);
    displayedColumns    = ['user_id', 'lastname', 'firstname', 'status', 'mail', 'actions'];


    @ViewChild(MatPaginator) paginator: MatPaginator;
    @ViewChild(MatSort) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim(); // Remove whitespace
        filterValue = filterValue.toLowerCase(); // MatTableDataSource defaults to lowercase matches
        this.dataSource.filter = filterValue;
    }

    constructor(changeDetectorRef: ChangeDetectorRef, media: MediaMatcher, public http: HttpClient, private notify: NotificationService, public dialog: MatDialog, private headerService: HeaderService) {
        super(http, ['users']);
        $j("link[href='merged_css.php']").remove();
        this.mobileQuery = media.matchMedia('(max-width: 768px)');
        this._mobileQueryListener = () => changeDetectorRef.detectChanges();
        this.mobileQuery.addListener(this._mobileQueryListener);
    }

    ngOnDestroy(): void {
        this.mobileQuery.removeListener(this._mobileQueryListener);
    }

    ngOnInit(): void {
        this.headerService.setHeader(this.lang.administration + ' ' + this.lang.users);
        window['MainHeaderComponent'].setSnav(this.sidenavLeft);
        window['MainHeaderComponent'].setSnavRight(null);

        this.coreUrl = angularGlobals.coreUrl;
        this.user = angularGlobals.user;
        this.loading = true;

        this.http.get(this.coreUrl + 'rest/users')
            .subscribe((data: any) => {
                this.data = data['users'];
                this.quota = data['quota'];
                if (this.quota.actives > this.quota.userQuota) {
                    this.notify.error(this.lang.quotaExceeded);
                }

                this.loading = false;
                setTimeout(() => {
                    this.dataSource = new MatTableDataSource(this.data);
                    this.dataSource.paginator = this.paginator;
                    this.dataSource.sort = this.sort;
                }, 0);
            }, () => {
                location.href = "index.php";
            });
    }

    activateUser(user: any) {
        let r = confirm(this.lang.confirmAction + ' ' + this.lang.authorize + ' « ' + user.user_id + ' »');

        if (r) {
            user.enabled = 'Y';
            this.http.put(this.coreUrl + 'rest/users/' + user.id, user)
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
                    user.enabled = 'N';
                    this.notify.error(err.error.errors);
                });
        }
    }

    deleteUser(user: any, mode: string) {
        user.mode = mode;

        this.http.get(this.coreUrl + 'rest/users/' + user.id + '/isDeletable')
            .subscribe((response: any) => {
                if (response && response.hasOwnProperty('errors')) {
                    this.notify.error(response.errors);
                } else {
                    user.isDeletable = response.isDeletable

                    if (response.isDeletable) {
                        this.config = {
                            data: {
                                userDestRedirect: user,
                                isDeletable: response.isDeletable,
                                redirectListInstances: response.listInstances,
                                redirectListModels: response.listTemplates
                            }
                        };
                    } else {
                        this.config = {
                            data: {
                                userDestRedirect: user,
                                isDeletable: response.isDeletable,
                                listInstanceEntities: response.listInstanceEntities,
                                listTemplateEntities: response.listTemplateEntities
                            }
                        };

                    }

                    //open modale
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
                                        if (element.item_mode == 'dest' && element.item_id == user.user_id) {
                                            element.item_id = user.redirectDestResUserId;
                                        }
                                    });
                                });

                                user.redirectListInstances = result.redirectListInstances;
                            }

                            if (user.inDiffListDest && user.isResDestUser) { //user is inDiffListDest and isResDestUser

                                //update listModels
                                this.http.put(this.coreUrl + 'rest/listTemplates/entityDest/itemId/' + user.user_id, user)
                                    .subscribe((data: any) => {
                                        if (data.errors) {
                                            this.notify.error(data.errors);
                                        } else {

                                            //update listInstances
                                            this.http.put(this.coreUrl + 'rest/listinstances', user.redirectListInstances)
                                                .subscribe((data: any) => {
                                                    if (data.errors) {
                                                        this.notify.error(data.errors);
                                                    } else {

                                                        //delete user
                                                        if (user.mode == 'delete') {
                                                            this.http.delete(this.coreUrl + 'rest/users/' + user.id)
                                                                .subscribe(() => {
                                                                    for (let i in this.data) {
                                                                        if (this.data[i].id == user.id) {
                                                                            this.data.splice(Number(i), 1);
                                                                        }
                                                                    }
                                                                    this.dataSource = new MatTableDataSource(this.data);
                                                                    this.dataSource.paginator = this.paginator;
                                                                    this.dataSource.sort = this.sort;

                                                                    if (this.quota.userQuota && user.enabled == 'Y') {
                                                                        this.quota.actives--;
                                                                    } else if (this.quota.userQuota && user.enabled == 'N') {
                                                                        this.quota.inactives--;
                                                                    }

                                                                    this.notify.success(this.lang.userDeleted + ' « ' + user.user_id + ' »');

                                                                    //end delete user
                                                                }, (err) => {
                                                                    this.notify.error(err.error.errors);
                                                                });
                                                            //suspend user
                                                        } else if (user.mode == 'suspend') {
                                                            this.http.put(this.coreUrl + 'rest/users/' + user.id + '/suspend', user)
                                                                .subscribe(() => {
                                                                    user.enabled = 'N';
                                                                    this.notify.success(this.lang.userSuspended);
                                                                    if (this.quota.userQuota) {
                                                                        this.quota.inactives++;
                                                                        this.quota.actives--;
                                                                    }

                                                                }, (err) => {
                                                                    user.enabled = 'Y';
                                                                    this.notify.error(err.error.errors);
                                                                });
                                                        }
                                                    }
                                                    //end update listInstances
                                                }, (err) => {
                                                    this.notify.error(err.error.errors);
                                                });
                                        }
                                        //end update listModels
                                    }, (err) => {
                                        this.notify.error(err.error.errors);
                                    });

                            } else if (user.inDiffListDest && !user.isResDestUser) { //user is inDiffListDest
                                //update listModels
                                this.http.put(this.coreUrl + 'rest/listTemplates/entityDest/itemId/' + user.user_id, user)
                                    .subscribe((data: any) => {
                                        if (data.errors) {
                                            this.notify.error(data.errors);
                                        } else {

                                            //delete user
                                            if (user.mode == 'delete') {
                                                this.http.delete(this.coreUrl + 'rest/users/' + user.id)
                                                    .subscribe(() => {
                                                        for (let i in this.data) {
                                                            if (this.data[i].id == user.id) {
                                                                this.data.splice(Number(i), 1);
                                                            }
                                                        }
                                                        this.dataSource = new MatTableDataSource(this.data);
                                                        this.dataSource.paginator = this.paginator;
                                                        this.dataSource.sort = this.sort;

                                                        if (this.quota.userQuota && user.enabled == 'Y') {
                                                            this.quota.actives--;
                                                        } else if (this.quota.userQuota && user.enabled == 'N') {
                                                            this.quota.inactives--;
                                                        }

                                                        this.notify.success(this.lang.userDeleted + ' « ' + user.user_id + ' »');

                                                        //end delete user
                                                    }, (err) => {
                                                        this.notify.error(err.error.errors);
                                                    });
                                                //suspend user
                                            } else if (user.mode == 'suspend') {
                                                this.http.put(this.coreUrl + 'rest/users/' + user.id + '/suspend', user)
                                                    .subscribe(() => {
                                                        user.enabled = 'N';
                                                        this.notify.success(this.lang.userSuspended);
                                                        if (this.quota.userQuota) {
                                                            this.quota.inactives++;
                                                            this.quota.actives--;
                                                        }

                                                    }, (err) => {
                                                        user.enabled = 'Y';
                                                        this.notify.error(err.error.errors);
                                                    });
                                            }
                                        }
                                        //end update listModels
                                    }, (err) => {
                                        this.notify.error(err.error.errors);
                                    });

                            } else if (!user.inDiffListDest && user.isResDestUser) { //user isResDestUser
                                //update listInstances
                                this.http.put(this.coreUrl + 'rest/listinstances', user.redirectListInstances)
                                    .subscribe((data: any) => {
                                        if (data && data.hasOwnProperty('errors')) {
                                            this.notify.error(data.errors);
                                        } else {

                                            //delete user
                                            if (user.mode == 'delete') {
                                                this.http.delete(this.coreUrl + 'rest/users/' + user.id)
                                                    .subscribe(() => {
                                                        for (let i in this.data) {
                                                            if (this.data[i].id == user.id) {
                                                                this.data.splice(Number(i), 1);
                                                            }
                                                        }
                                                        this.dataSource = new MatTableDataSource(this.data);
                                                        this.dataSource.paginator = this.paginator;
                                                        this.dataSource.sort = this.sort;

                                                        if (this.quota.userQuota && user.enabled == 'Y') {
                                                            this.quota.actives--;
                                                        } else if (this.quota.userQuota && user.enabled == 'N') {
                                                            this.quota.inactives--;
                                                        }

                                                        this.notify.success(this.lang.userDeleted + ' « ' + user.user_id + ' »');

                                                        //end delete user
                                                    }, (err) => {
                                                        this.notify.error(err.error.errors);
                                                    });
                                                //suspend user
                                            } else if (user.mode == 'suspend') {
                                                this.http.put(this.coreUrl + 'rest/users/' + user.id + '/suspend', user)
                                                    .subscribe(() => {
                                                        user.enabled = 'N';
                                                        this.notify.success(this.lang.userSuspended);
                                                        if (this.quota.userQuota) {
                                                            this.quota.inactives++;
                                                            this.quota.actives--;
                                                        }

                                                    }, (err) => {
                                                        user.enabled = 'Y';
                                                        this.notify.error(err.error.errors);
                                                    });
                                            }
                                        }
                                        //end update listInstances
                                    }, (err) => {
                                        this.notify.error(err.error.errors);
                                    });

                            } else if (!user.inDiffListDest && !user.isResDestUser) { //user is not inDiffListDest and is not isResDestUser

                                //delete user
                                if (user.mode == 'delete') {
                                    this.http.delete(this.coreUrl + 'rest/users/' + user.id)
                                        .subscribe(() => {
                                            for (let i in this.data) {
                                                if (this.data[i].id == user.id) {
                                                    this.data.splice(Number(i), 1);
                                                }
                                            }
                                            this.dataSource = new MatTableDataSource(this.data);
                                            this.dataSource.paginator = this.paginator;
                                            this.dataSource.sort = this.sort;

                                            if (this.quota.userQuota && user.enabled == 'Y') {
                                                this.quota.actives--;
                                            } else if (this.quota.userQuota && user.enabled == 'N') {
                                                this.quota.inactives--;
                                            }

                                            this.notify.success(this.lang.userDeleted + ' « ' + user.user_id + ' »');

                                            //end delete user
                                        }, (err) => {
                                            this.notify.error(err.error.errors);
                                        });
                                    //suspend user
                                } else if (user.mode == 'suspend') {
                                    this.http.put(this.coreUrl + 'rest/users/' + user.id + '/suspend', user)
                                        .subscribe(() => {
                                            user.enabled = 'N';
                                            this.notify.success(this.lang.userSuspended);
                                            if (this.quota.userQuota) {
                                                this.quota.inactives++;
                                                this.quota.actives--;
                                            }

                                        }, (err) => {
                                            user.enabled = 'Y';
                                            this.notify.error(err.error.errors);
                                        });
                                }
                            }
                        }

                        //close modale
                    });
                }
                //end isDeletable
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

}
@Component({
    templateUrl: "users-administration-redirect-modal.component.html",
    styleUrls: ['users-administration-redirect-modal.scss']
})
export class UsersAdministrationRedirectModalComponent extends AutoCompletePlugin {
    lang: any               = LANG;
    loadModel: boolean      = false;
    loadInstance: boolean   = false;
    modalTitle: string      = this.lang.confirmAction;

    constructor(public http: HttpClient, @Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<UsersAdministrationRedirectModalComponent>) {
        super(http, ['users']);
    }

    ngOnInit(): void {

        if (this.data.isDeletable) {
            //get listModel
            if (this.data.redirectListModels.length > 0) {
                this.data.inDiffListDest = true;
            }

            //get listInstances
            if (this.data.redirectListInstances.length > 0) {
                this.data.isResDestUser = true;
            }
        } else {
            if (this.data.userDestRedirect.mode == 'delete') {
                this.modalTitle = this.lang.unableToDelete;
            } else {
                this.modalTitle = this.lang.unableToSuspend;
            }
            //get listModel
            if (this.data.listTemplateEntities.length > 0) {
                this.data.inTemplateList = true;
            }

            //get listInstances
            if (this.data.listInstanceEntities.length > 0) {
                this.data.inInstanceList = true;
            }
        }

    }

    sendFunction() {
        var valid = true;

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
