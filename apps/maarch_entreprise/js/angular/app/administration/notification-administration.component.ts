import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { MediaMatcher } from '@angular/cdk/layout';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { LANG } from '../translate.component';
import { NotificationService } from '../notification.service';

declare var $j: any;

declare var angularGlobals: any;


@Component({
    templateUrl: "../../../../Views/notification-administration.component.html",
    providers: [NotificationService]
})
export class NotificationAdministrationComponent implements OnInit {
    mobileQuery: MediaQueryList;
    private _mobileQueryListener: () => void;
    coreUrl: string;

    creationMode: boolean;
    notification: any = {
        diffusionType_label: null
    };
    loading: boolean = false;
    lang: any = LANG;

    constructor(changeDetectorRef: ChangeDetectorRef, media: MediaMatcher, public http: HttpClient, private route: ActivatedRoute, private router: Router, private notify: NotificationService) {
        $j("link[href='merged_css.php']").remove();
        this.mobileQuery = media.matchMedia('(max-width: 768px)');
        this._mobileQueryListener = () => changeDetectorRef.detectChanges();
        this.mobileQuery.addListener(this._mobileQueryListener);
    }

    ngOnDestroy(): void {
        this.mobileQuery.removeListener(this._mobileQueryListener);
    }

    ngOnInit(): void {
        this.loading = true;
        this.coreUrl = angularGlobals.coreUrl;

        this.route.params.subscribe(params => {
            if (typeof params['identifier'] == "undefined") {
                this.creationMode = true;
                this.http.get(this.coreUrl + 'rest/administration/notifications/new')
                    .subscribe((data: any) => {
                        this.notification = data.notification;
                        this.notification.attachfor_properties = [];
                        this.loading = false;
                    }, (err) => {
                        this.notify.error(err.error.errors);
                    });
            } else {
                this.creationMode = false;
                this.http.get(this.coreUrl + 'rest/notifications/' + params['identifier'])
                    .subscribe((data: any) => {
                        this.notification = data.notification;
                        this.notification.attachfor_properties = [];
                        this.loading = false;
                    }, (err) => {
                        this.notify.error(err.error.errors);
                    });
            }
        });

    }

    createScript() {
        this.http.post(this.coreUrl + 'rest/scriptNotification', this.notification)
            .subscribe((data: any) => {
                this.notification.scriptcreated = data;
                this.notify.success(this.lang.scriptCreated);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    onSubmit() {
        if (this.creationMode) {
            this.notification.is_enabled = "Y";
            this.http.post(this.coreUrl + 'rest/notifications', this.notification)
                .subscribe((data: any) => {
                    this.router.navigate(['/administration/notifications']);
                    this.notify.success(this.lang.notificationAdded);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            this.http.put(this.coreUrl + 'rest/notifications/' + this.notification.notification_sid, this.notification)
                .subscribe((data: any) => {
                    this.router.navigate(['/administration/notifications']);
                    this.notify.success(this.lang.notificationUpdated);
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    toggleNotif() {
        if (this.notification.is_enabled == "Y") {
            this.notification.is_enabled = "N";
        } else {
            this.notification.is_enabled = "Y";
        }
        this.http.put(this.coreUrl + 'rest/notifications/' + this.notification.notification_sid, this.notification)
            .subscribe((data: any) => {
                this.notify.success(this.lang.notificationUpdated);
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    isNumber(val:any) {
        return $j.isNumeric(val);
    }
}
