import { Injectable } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';
import { LatinisePipe } from 'ngx-pipes';

@Injectable()
export class DashboardService {

    tileTypes: any = {
        myLastResources : {
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
                    route: null
                },
                {
                    id: 'chart',
                    route: null
                }
            ]
        },
        basket : {
            menus : [
                'view',
                'delete'
            ],
            views: [
                {
                    id: 'list',
                    route: '/process/users/:userId/groups/:groupId/baskets/:basketId/resId/:resId'
                },
                {
                    id: 'resume',
                    route: '/process/users/:userId/groups/:groupId/baskets/:basketId'
                },
                {
                    id: 'chart',
                    route: '/process/users/:userId/groups/:groupId/baskets/:basketId'
                }
            ]
        },
        searchTemplate : {
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
        },
        followedMail : {
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
                    route: '/followed'
                },
                {
                    id: 'chart',
                    route: '/followed'
                }
            ]
        },
        folder : {
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
                    route: '/folders/:folderId'
                },
                {
                    id: 'chart',
                    route: '/folders/:folderId'
                }
            ]
        },
        externalSignatoryBook : {
            menus : [
                'view',
                'delete'
            ],
            views: [
                {
                    id: 'list',
                    route: ':signatoryBookPathresources/dist/documents/:resId'
                },
                {
                    id: 'resume',
                    route: ':signatoryBookPathresources/dist/home'
                },
                {
                    id: 'chart',
                    route: ':signatoryBookPathresources/dist/home'
                }
            ]
        },
        shortcut : {
            menus : [
                'delete'
            ],
            views: []
        },
    };

    constructor(
        public translate: TranslateService,
        private latinisePipe: LatinisePipe,
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
}
