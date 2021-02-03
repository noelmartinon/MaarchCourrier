import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { of } from 'rxjs';
import { catchError, tap } from 'rxjs/operators';

interface Tiles {
    'myLastResources': Tile;
    'basket': Tile;
    // 'searchTemplate': Tile;
    'followedMail': Tile;
    'folder': Tile;
    'externalSignatoryBook': Tile;
    'shortcut': Tile;
}

interface Tile {
    'icon': string; // icon of tile
    'menus': ('delete' | 'view')[]; // action of tile
    'views': TileView[]; // views tile
}

interface TileView {
    'id': 'list' | 'resume' | 'chart'; // identifier
    'route': string; // router when click on tile
    'viewDocRoute'?: string; // router when view a doc (usefull for list view)
}

@Injectable()
export class DashboardService {

    tileTypes: Tiles = {
        myLastResources : {
            icon: 'fa fa-history',
            menus : [
                'view',
                'delete'
            ],
            views: [
                {
                    id: 'list',
                    route: '/resources/:resId',
                    viewDocRoute: '/resources/:resId/thumbnail'
                },
                {
                    id: 'resume',
                    route: null
                },
                {
                    id: 'chart',
                    route: null
                }
            ]
        },
        basket: {
            icon: 'fa fa-inbox',
            menus: [
                'view',
                'delete'
            ],
            views: [
                {
                    id: 'list',
                    route: '/process/users/:userId/groups/:groupId/baskets/:basketId/resId/:resId',
                    viewDocRoute: '/resources/:resId/thumbnail'
                },
                {
                    id: 'resume',
                    route: '/basketList/users/:userId/groups/:groupId/baskets/:basketId'
                },
                {
                    id: 'chart',
                    route: '/basketList/users/:userId/groups/:groupId/baskets/:basketId'
                }
            ]
        },
        /*searchTemplate : {
            icon: 'fa fa-search',
            menus : [
                'view',
                'delete'
            ],
            views: [
                {
                    id: 'list',
                    route: '/resources/:resId'
                },
                {
                    id: 'resume',
                    route: '/search'
                },
                {
                    id: 'chart',
                    route: '/search'
                }
            ]
        },*/
        followedMail : {
            icon: 'fa fa-star',
            menus : [
                'view',
                'delete'
            ],
            views: [
                {
                    id: 'list',
                    route: '/resources/:resId',
                    viewDocRoute: '/resources/:resId/thumbnail'
                },
                {
                    id: 'resume',
                    route: '/followed'
                },
                {
                    id: 'chart',
                    route: '/followed'
                }
            ]
        },
        folder : {
            icon: 'fa fa-folder',
            menus : [
                'view',
                'delete'
            ],
            views: [
                {
                    id: 'list',
                    route: '/resources/:resId',
                    viewDocRoute: '/resources/:resId/thumbnail'
                },
                {
                    id: 'resume',
                    route: '/folders/:folderId'
                },
                {
                    id: 'chart',
                    route: '/folders/:folderId'
                }
            ]
        },
        externalSignatoryBook : {
            icon: 'fas fa-pen-nib',
            menus : [
                'view',
                'delete'
            ],
            views: [
                {
                    id: 'list',
                    route: ':maarchParapheurUrl/dist/documents/:id',
                    viewDocRoute: null
                },
                {
                    id: 'resume',
                    route: ':maarchParapheurUrl/dist/home'
                }
            ]
        },
        shortcut : {
            icon: null,
            menus : [
                'delete'
            ],
            views: [
                {
                    id: 'resume',
                    route: ':privRoute'
                }
            ]
        },
    };

    constructor(
        public http: HttpClient,
        public translate: TranslateService,
        private notify: NotificationService
    ) { }

    getTile(id: string) {
        return this.tileTypes[id];
    }

    getTileTypes() {
        return Object.keys(this.tileTypes);
    }

    getViewsByTileType(tileType: string) {
        return this.tileTypes[tileType].views;
    }

    getChartMode() {
        return [
            'doctype',
            'status'
        ];
    }

    getFormatedRoute(route: string, data: any) {
        const regex = /:\w*/g;
        const res = route.match(regex);

        let formatedRoute = route;
        let errors = [];

        if (res !== null) {
            let routeIdValue = null;
            errors = res.slice();

            res.forEach((routeId: any) => {
                routeIdValue = data[routeId.replace(':', '')];
                if (routeIdValue !== undefined) {
                    formatedRoute = formatedRoute.replace(routeId, routeIdValue);
                    errors.splice(errors.indexOf(routeId), 1);
                }
            });
        }

        if (errors.length === 0) {
            return formatedRoute;
        } else {
            this.notify.error(errors + ' not found');
            return false;
        }
    }
}
