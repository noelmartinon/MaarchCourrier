import { Component, Input, OnInit, Renderer2 } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { TranslateService } from '@ngx-translate/core';
import { NotificationService } from '@service/notification/notification.service';
import { catchError, map, tap } from 'rxjs/operators';
import { of } from 'rxjs';
import { MatDialog } from '@angular/material/dialog';
import { FunctionsService } from '@service/functions.service';
import { HeaderService } from '@service/header.service';

@Component({
    selector: 'app-history-diffusions-list',
    templateUrl: 'history-diffusions-list.component.html',
    styleUrls: ['history-diffusions-list.component.scss'],
})
export class HistoryDiffusionsListComponent implements OnInit {


    roles: any = [];
    loading: boolean = true;
    availableRoles: any[] = [];
    currentEntityId: number = 0;
    userDestList: any[] = [];

    diffListHistory: any[] = [];


    /**
     * Ressource identifier to load listinstance (Incompatible with templateId)
     */
    @Input() resId: number = null;

    /**
     * Expand all roles
     */
    @Input() expanded: boolean = true;


    constructor(
        public translate: TranslateService,
        public http: HttpClient,
        private notify: NotificationService,
        private renderer: Renderer2,
        public dialog: MatDialog,
        public functions: FunctionsService,
        private headerService: HeaderService
    ) { }

    async ngOnInit(): Promise<void> {
        await this.initRoles();
        if (this.resId !== null) {
            this.getListinstanceHistory();
        }
        this.loading = false;
    }

    getListinstanceHistory() {
        this.diffListHistory = [
            {
                user: 'Bernard BLIER',
                creationDate: '2020-10-06 17:02:19.558904',
                listinstance: {
                    'dest': {
                        'label': 'Attributaire',
                        'items': [
                            {
                                'listinstance_id': 62,
                                'item_mode': 'dest',
                                'item_type': 'user',
                                'itemSerialId': 19,
                                'itemId': 'bbain',
                                'itemLabel': 'Barbara BAIN',
                                'itemSubLabel': 'Pôle Jeunesse et Sport',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'cc': {
                        'label': 'En copie',
                        'items': [
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'avis': {
                        'label': 'Pour avis',
                        'items': []
                    }
                }
            },
            {
                user: 'Bernard BLIER',
                creationDate: '2020-10-06 17:02:19.558904',
                listinstance: {
                    'dest': {
                        'label': 'Attributaire',
                        'items': [
                            {
                                'listinstance_id': 62,
                                'item_mode': 'dest',
                                'item_type': 'user',
                                'itemSerialId': 19,
                                'itemId': 'bbain',
                                'itemLabel': 'Barbara BAIN',
                                'itemSubLabel': 'Pôle Jeunesse et Sport',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'cc': {
                        'label': 'En copie',
                        'items': [
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'avis': {
                        'label': 'Pour avis',
                        'items': []
                    }
                }
            },
            {
                user: 'Bernard BLIER',
                creationDate: '2020-10-06 17:02:19.558904',
                listinstance: {
                    'dest': {
                        'label': 'Attributaire',
                        'items': [
                            {
                                'listinstance_id': 62,
                                'item_mode': 'dest',
                                'item_type': 'user',
                                'itemSerialId': 19,
                                'itemId': 'bbain',
                                'itemLabel': 'Barbara BAIN',
                                'itemSubLabel': 'Pôle Jeunesse et Sport',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'cc': {
                        'label': 'En copie',
                        'items': [
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'avis': {
                        'label': 'Pour avis',
                        'items': []
                    }
                }
            },
            {
                user: 'Bernard BLIER',
                creationDate: '2020-10-06 17:02:19.558904',
                listinstance: {
                    'dest': {
                        'label': 'Attributaire',
                        'items': [
                            {
                                'listinstance_id': 62,
                                'item_mode': 'dest',
                                'item_type': 'user',
                                'itemSerialId': 19,
                                'itemId': 'bbain',
                                'itemLabel': 'Barbara BAIN',
                                'itemSubLabel': 'Pôle Jeunesse et Sport',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'cc': {
                        'label': 'En copie',
                        'items': [
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'avis': {
                        'label': 'Pour avis',
                        'items': []
                    }
                }
            },
            {
                user: 'Bernard BLIER',
                creationDate: '2020-10-06 17:02:19.558904',
                listinstance: {
                    'dest': {
                        'label': 'Attributaire',
                        'items': [
                            {
                                'listinstance_id': 62,
                                'item_mode': 'dest',
                                'item_type': 'user',
                                'itemSerialId': 19,
                                'itemId': 'bbain',
                                'itemLabel': 'Barbara BAIN',
                                'itemSubLabel': 'Pôle Jeunesse et Sport',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'cc': {
                        'label': 'En copie',
                        'items': [
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'avis': {
                        'label': 'Pour avis',
                        'items': []
                    }
                }
            },
            {
                user: 'Bernard BLIER',
                creationDate: '2020-10-06 17:02:19.558904',
                listinstance: {
                    'dest': {
                        'label': 'Attributaire',
                        'items': [
                            {
                                'listinstance_id': 62,
                                'item_mode': 'dest',
                                'item_type': 'user',
                                'itemSerialId': 19,
                                'itemId': 'bbain',
                                'itemLabel': 'Barbara BAIN',
                                'itemSubLabel': 'Pôle Jeunesse et Sport',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'cc': {
                        'label': 'En copie',
                        'items': [
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            },
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'avis': {
                        'label': 'Pour avis',
                        'items': []
                    }
                }
            },
            {
                creationDate: '2020-11-06 17:02:19.558904',
                listinstance: {
                    'dest': {
                        'label': 'Attributaire',
                        'items': [
                            {
                                'listinstance_id': 62,
                                'item_mode': 'dest',
                                'item_type': 'user',
                                'itemSerialId': 19,
                                'itemId': 'bbain',
                                'itemLabel': 'Barbara BAIN',
                                'itemSubLabel': 'Pôle Jeunesse et Sport',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'cc': {
                        'label': 'En copie',
                        'items': [
                            {
                                'listinstance_id': 63,
                                'item_mode': 'cc',
                                'item_type': 'entity',
                                'itemSerialId': 1,
                                'itemId': 'VILLE',
                                'itemLabel': 'Ville de Maarch-les-bains',
                                'itemSubLabel': '',
                                'difflist_type': 'entity_id',
                                'process_date': null,
                                'process_comment': null
                            }
                        ]
                    },
                    'avis': {
                        'label': 'Pour avis',
                        'items': []
                    }
                }
            }
        ];
        /*return new Promise((resolve, reject) => {
            this.http.get(`../rest/resources/${this.resId}/fields/destination?alt=true`).pipe(
                tap((data: any) => {
                    this.currentEntityId = data.field;
                    diffusions.forEach((element: any) => {
                        if (!this.functions.empty(this.diffList[element.item_mode])) {
                            this.diffList.listinstance[element.item_mode].items.push(element);
                        }
                    });
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });*/
    }

    initRoles() {
        return new Promise((resolve, reject) => {
            this.http.get(`../rest/roles`).pipe(
                map((data: any) => {
                    data.roles = data.roles.map((role: any) => {
                        return {
                            ...role,
                            id: role.id,
                        };
                    });
                    return data.roles;
                }),
                tap((roles: any) => {
                    this.availableRoles = roles;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }
}
