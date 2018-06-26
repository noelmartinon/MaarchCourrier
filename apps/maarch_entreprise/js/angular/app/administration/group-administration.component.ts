import { ChangeDetectorRef, Component, OnInit, ViewChild } from '@angular/core';
import { MediaMatcher } from '@angular/cdk/layout';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';
import { MatPaginator, MatTableDataSource, MatSort} from '@angular/material';

declare function $j(selector: any) : any;
declare const angularGlobals : any;


@Component({
    templateUrl: "../../../../Views/group-administration.component.html",
    providers   : [NotificationService]
})
export class GroupAdministrationComponent implements OnInit {

    private _mobileQueryListener    : () => void;
    mobileQuery                     : MediaQueryList;

    coreUrl                         : string;
    lang                            : any       = LANG;
    loading                         : boolean   = false;

    group                           : any       = {
        security                    : {}
    };
    creationMode                    : boolean;

    displayedColumns    = ['firstname', 'lastname'];
    dataSource          : any;
    basketsDisplayedColumns         = ['basket_name', 'basket_desc'];
    basketsDataSource               : any;


    @ViewChild('paginatorBaskets') paginatorBaskets: MatPaginator;
    @ViewChild(MatPaginator) paginator: MatPaginator;
    @ViewChild(MatSort) sort: MatSort;
    applyFilter(filterValue: string) {
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.dataSource.filter = filterValue;
    }
    applyBasketsFilter(filterValue: string) {
        filterValue = filterValue.trim();
        filterValue = filterValue.toLowerCase();
        this.basketsDataSource.filter = filterValue;
    }

    constructor(changeDetectorRef: ChangeDetectorRef, media: MediaMatcher,public http: HttpClient, private route: ActivatedRoute, private router: Router, private notify: NotificationService) {
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
                this.creationMode = true;
                this.loading = false;
            } else {
                this.creationMode = false;
                this.http.get(this.coreUrl + "rest/groups/" + params['id'] + "/details")
                    .subscribe((data : any) => {
                        this.group = data['group'];
                        this.loading = false;
                        setTimeout(() => {
                            this.dataSource = new MatTableDataSource(this.group.users);
                            this.dataSource.paginator = this.paginator;
                            this.dataSource.sort = this.sort;
                            this.basketsDataSource = new MatTableDataSource(this.group.baskets);
                            this.basketsDataSource.paginator = this.paginatorBaskets;
                            this.basketsDataSource.sort = this.sort;
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
                .subscribe((data : any) => {
                    this.notify.success(this.lang.groupAdded);
                    this.router.navigate(["/administration/groups/" + data.group]);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.http.put(this.coreUrl + "rest/groups/" + this.group['id'] , {"description" : this.group['group_desc'], "security" : this.group['security']})
                .subscribe(() => {
                    this.notify.success(this.lang.groupUpdated);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }

    }

    updateService(service: any) {
        this.http.put(this.coreUrl + "rest/groups/" + this.group['id'] + "/services/" + service['id'], service)
            .subscribe(() => {
                this.notify.success(this.lang.groupServicesUpdated);
            }, (err) => {
                service.checked = !service.checked;
                this.notify.error(err.error.errors);
            });
    }
}
