import { ChangeDetectorRef, Component, OnInit, ViewChild, Inject } from '@angular/core';
import { MediaMatcher } from '@angular/cdk/layout';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { MatPaginator, MatTableDataSource, MatSort, MatDialog, MatDialogRef, MAT_DIALOG_DATA, MatSidenav } from '@angular/material';
import { NotificationService } from '../../notification.service';
import { HeaderService }        from '../../../service/header.service';
import { AutoCompletePlugin } from '../../../plugins/autocomplete.plugin';
//import { exists } from 'fs';

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
        this.headerService.headerMessage = this.lang.administration + ' ' + this.lang.users;
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

    suspendUser(user: any) {
        if (user.inDiffListDest) {
            user.mode = 'up';
            this.userDestRedirect = user;
            this.http.get(this.coreUrl + 'rest/listTemplates/entityDest/itemId/' + user.user_id)
                .subscribe((data: any) => {
                    this.userDestRedirectModels = data.listTemplates;
                    this.config = { data: { userDestRedirect: this.userDestRedirect, userDestRedirectModels: this.userDestRedirectModels } };
                    this.dialogRef = this.dialog.open(UsersAdministrationRedirectModalComponent, this.config);
                    this.dialogRef.afterClosed().subscribe((result: string) => {
                        if (result) {
                            user.enabled = 'N';
                            user.redirectListModels = result;
                            //first, update listModels
                            this.http.put(this.coreUrl + 'rest/listTemplates/entityDest/itemId/' + user.user_id, user)
                                .subscribe((data: any) => {
                                    if (data.errors) {
                                        user.enabled = 'Y';
                                        this.notify.error(data.errors);
                                    } else {
                                        //then suspend user
                                        this.http.put(this.coreUrl + 'rest/users/' + user.id, user)
                                            .subscribe(() => {
                                                user.inDiffListDest = false;
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
                                }, (err) => {
                                    this.notify.error(err.error.errors);
                                });
                        }
                        this.dialogRef = null;
                    });

                }, (err) => {
                    console.log(err);
                    location.href = "index.php";
                });
        } else {
            let r = confirm(this.lang.confirmAction + ' ' + this.lang.suspend + ' « ' + user.user_id + ' »');

            if (r) {
                user.enabled = 'N';
                this.http.put(this.coreUrl + 'rest/users/' + user.id, user)
                    .subscribe(() => {
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

    deleteUser(user: any) {
            
        user.mode = 'del';
        this.userDestRedirect = user;

        this.config = { 
            data: { 
                userDestRedirect: this.userDestRedirect
            } 
        };

        //open modale
        this.dialogRef = this.dialog.open(UsersAdministrationRedirectModalComponent, this.config);
        this.dialogRef.afterClosed().subscribe((result: any) => {

            if(result) {
               
                if (result.userDestRedirectModels) {
                    user.redirectListModels = result.userDestRedirectModels;
                }

                if (result.redirectDestResUserId) {
                    user.redirectDestResUserId = result.redirectDestResUserId;

                    this.listinstances = result.listinstances;
                    this.listinstances.forEach((list: any) => {
                        list.listinstances.forEach((element:any) => {
                            if(element.item_mode == 'dest') {
                                element.item_id = user.redirectDestResUserId;
                            }
                        });
                    });

                    user.listinstances = this.listinstances;
                }

                //if user inDiffListDest and isResDestUser
                if (user.inDiffListDest && user.isResDestUser) {
                   
                    //update listModels
                    this.updateListModel = false;
                    this.http.put(this.coreUrl + 'rest/listTemplates/entityDest/itemId/' + user.user_id, user)
                    .subscribe((data: any) => {
                        if (data.errors) {
                            this.notify.error(data.errors);
                        } else {

                            //update listInstances
                            this.updateListInstance = false;
                            this.http.put(this.coreUrl + 'rest/listinstances', user)
                            .subscribe((data: any) => {
                                if (data.errors) {
                                    this.notify.error(data.errors);
                                } else {

                                    //delete user
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
            
            //user inDiffListDest
                } else if (user.inDiffListDest && !user.isResDestUser) {
                    
                    //update listModels
                    this.updateListModel = false;
                    this.http.put(this.coreUrl + 'rest/listTemplates/entityDest/itemId/' + user.user_id, user)
                    .subscribe((data: any) => {
                        if (data.errors) {
                            this.notify.error(data.errors);
                        } else {
                            //delete user
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
                        }
                    //end update listModels
                    }, (err) => {
                        this.notify.error(err.error.errors);
                    });

                //user isResDestUser
                } else if (!user.inDiffListDest && user.isResDestUser) {

                    //update listInstances
                    this.http.put(this.coreUrl + 'rest/listinstances', user)
                    .subscribe((data: any) => {
                        if (data.errors) {
                            this.notify.error(data.errors);
                        } else {

                            //delete user
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
                        }
                    //end update listInstances
                    }, (err) => {
                        this.notify.error(err.error.errors);
                    });

                    
                //user is not inDiffListDest and is not isResDestUser
                } else if (!user.inDiffListDest && !user.isResDestUser) {                    
                    
                    //delete user
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
                }
            }

        //close modale
        });
    }
}
@Component({
    templateUrl: "users-administration-redirect-modal.component.html",
    styleUrls: ['users-administration-redirect-modal.scss']
})
export class UsersAdministrationRedirectModalComponent extends AutoCompletePlugin {
    lang: any = LANG;
    loadModel: boolean      = false;
    loadInstance: boolean   = false;

    constructor(public http: HttpClient, @Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<UsersAdministrationRedirectModalComponent>) {
        super(http, ['users']);
    }

    ngOnInit(): void {
        
        //get listModel
        if(this.data.userDestRedirect.inDiffListDest) {
            this.loadModel      = true;
            this.http.get(this.coreUrl + 'rest/listTemplates/entityDest/itemId/' + this.data.userDestRedirect.user_id)
            .subscribe((dataDiff: any) => {
                this.data.userDestRedirectModels = dataDiff.listTemplates;
                this.loadModel = false;
            });
        }

        //get listInstances
        if(this.data.userDestRedirect.isResDestUser) {
            this.loadInstance   = true;
            this.http.get(this.coreUrl + 'rest/listinstances/dest/itemId/' + this.data.userDestRedirect.user_id)
                .subscribe((dataInstance: any) => {
                this.data.listinstances = dataInstance.listinstances;
                this.loadInstance = false;
            });
        }
    }

    sendFunction() {
        var valid = true;
        
        if(this.data.userDestRedirect.inDiffListDest) {
            this.data.userDestRedirectModels.forEach((element: any) => {
                if (!element.redirectUserId) {
                    valid = false;
                }
            });
        }

        if(this.data.userDestRedirect.isResDestUser) {
            if(!this.data.redirectDestResUserId) {
                valid = false;
            }
        }

        return valid;
    }
}
