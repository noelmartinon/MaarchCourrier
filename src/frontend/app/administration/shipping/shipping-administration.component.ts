import { ChangeDetectorRef, Component, OnInit, ViewChild } from '@angular/core';
import { MediaMatcher } from '@angular/cdk/layout';
import { HttpClient } from '@angular/common/http';
import { LANG } from '../../translate.component';
import { MatSidenav } from '@angular/material';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { ActivatedRoute, Router } from '@angular/router';

declare function $j(selector: any): any;


@Component({
    templateUrl: "shipping-administration.component.html",
    styleUrls: ['shipping-administration.component.scss'],
    providers: [NotificationService]
})
export class ShippingAdministrationComponent implements OnInit {

    @ViewChild('snav') public sidenavLeft: MatSidenav;
    @ViewChild('snav2') public sidenavRight: MatSidenav;

    mobileQuery: MediaQueryList;
    private _mobileQueryListener: () => void;

    lang: any = LANG;
    loading: boolean = false;
    creationMode: boolean = true;

    shipping: any = {
        label: '',
        description: '',
        options: {
            shapingOptions: ['addressPage'],
            sendMode: 'fast',
        },
        fee: {
            firstPagePrice: 0,
            nextPagePrice: 0,
            postagePrice: 0,
        },
        account: {
            id: '',
            password: ''
        },
        entities: []
    };

    entities: any[] = [];
    entitiesClone: any = null;
    shippingClone: any = null;

    shapingOptions: string[] = [
        'color',
        'duplexPrinting',
        'addressPage',
    ];

    sendModes: string[] = [
        'fast',
        'economic'
        // 'registered_mail',
        // 'registered_mail_ar'
    ];
    hidePassword: boolean = true;



    constructor(changeDetectorRef: ChangeDetectorRef, media: MediaMatcher, public http: HttpClient, private route: ActivatedRoute, private router: Router, private notify: NotificationService, private headerService: HeaderService) {
        $j("link[href='merged_css.php']").remove();
        this.mobileQuery = media.matchMedia('(max-width: 768px)');
        this._mobileQueryListener = () => changeDetectorRef.detectChanges();
        this.mobileQuery.addListener(this._mobileQueryListener);
    }

    ngOnInit(): void {

        window['MainHeaderComponent'].setSnav(this.sidenavLeft);
        window['MainHeaderComponent'].setSnavRight(this.sidenavRight);

        this.route.params.subscribe(params => {
            if (typeof params['id'] == "undefined") {
                this.headerService.setHeader(this.lang.shippingCreation);

                this.creationMode = true;

                this.http.get('../../rest/administration/shippings/new')
                    .subscribe((data: any) => {
                        console.log(data);
                        this.entities = data['entities'];
                        this.entitiesClone = JSON.parse(JSON.stringify(this.entities));
                        setTimeout(() => {
                            this.initEntitiesTree(this.entities);
                        }, 0);

                        this.shippingClone = JSON.parse(JSON.stringify(this.shipping));
                        this.loading = false;
                    }, (err) => {
                        this.notify.handleErrors(err);
                    });

                this.loading = false;

            } else {
                this.headerService.setHeader(this.lang.shippingModification);
                this.creationMode = false;

                this.http.get('../../rest/administration/shippings/' + params['id'])
                    .subscribe((data: any) => {
                        this.shipping = data['shipping']
                        this.entities = data['entities'];
                        this.entitiesClone = JSON.parse(JSON.stringify(this.entities));

                        setTimeout(() => {
                            this.initEntitiesTree(this.entities);
                        }, 0);

                        this.shippingClone = JSON.parse(JSON.stringify(this.shipping));
                        this.loading = false;
                    }, (err) => {
                        this.notify.handleErrors(err);
                    });

            }
        });
    }

    initEntitiesTree(entities: any) {
        $j('#jstree')
            .on('select_node.jstree', (e: any, data: any) => {
                if (data.event) {
                    data.instance.select_node(data.node.children_d);
                    this.shipping.entities = data.selected;
                }
            }).on('deselect_node.jstree', (e: any, data: any) => {
                this.shipping.entities = data.selected;
            })
            .jstree({
                "checkbox": { three_state: false },
                'core': {
                    force_text : true,
                    'themes': {
                        'name': 'proton',
                        'responsive': true
                    },
                    'data': entities
                },
                "plugins": ["checkbox", "search", "sort"]
            });
        var to: any = false;
        $j('#jstree_search').keyup(function () {
            if (to) { clearTimeout(to); }
            to = setTimeout(function () {
                var v = $j('#jstree_search').val();
                $j('#jstree').jstree(true).search(v);
            }, 250);
        });
    }

    onSubmit() {
        this.shipping.entities = $j('#jstree').jstree(true).get_checked();

        if (this.creationMode) {
            this.http.post('../../rest/administration/shippings', this.shipping)
            .subscribe((data: any) => {
                this.shippingClone = JSON.parse(JSON.stringify(this.shipping));
                this.notify.success(this.lang.shippingAdded);
                this.router.navigate(['/administration/shippings']);
            }, (err) => {
                this.notify.handleErrors(err);
            });
        } else {
            this.http.put('../../rest/administration/shippings/'+this.shipping.id, this.shipping)
            .subscribe((data: any) => {
                this.shippingClone = JSON.parse(JSON.stringify(this.shipping));
                this.notify.success(this.lang.shippingUpdated);
                this.router.navigate(['/administration/shippings']);
            }, (err) => {
                this.notify.handleErrors(err);
            }); 
        }
    }

    checkModif() {
        return (JSON.stringify(this.shippingClone) === JSON.stringify(this.shipping));
    }

    toggleShapingOption(option: string) {
        const index = this.shipping.options.shapingOptions.indexOf(option);
        if (index > -1) {
            this.shipping.options.shapingOptions.splice(index, 1);
        } else {
            this.shipping.options.shapingOptions.push(option);
        }
    }

    cancelModification() {
        this.shipping = JSON.parse(JSON.stringify(this.shippingClone));
        this.entities = JSON.parse(JSON.stringify(this.entitiesClone));
        $j('#jstree').jstree(true).destroy();
        this.initEntitiesTree(this.entities);
    }
}
