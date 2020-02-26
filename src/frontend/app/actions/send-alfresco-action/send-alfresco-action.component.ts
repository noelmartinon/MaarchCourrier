import { Component, OnInit, Inject, ViewChild } from '@angular/core';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '../../notes/note-editor.component';
import { map, tap, finalize, catchError, debounceTime, filter, switchMap } from 'rxjs/operators';
import { of } from 'rxjs';
import { FormControl } from '@angular/forms';
import { FunctionsService } from '../../../service/functions.service';

declare function $j(selector: any): any;

@Component({
    templateUrl: "send-alfresco-action.component.html",
    styleUrls: ['send-alfresco-action.component.scss'],
})
export class SendAlfrescoActionComponent implements OnInit {

    lang: any = LANG;
    loading: boolean = false;

    errors: any;

    alfrescoFolders: any[] = [];

    searchFolder = new FormControl();

    selectedFolder: number = null;

    resourcesErrors: any[] = [];
    noResourceToProcess: boolean = null;

    @ViewChild('noteEditor', { static: true }) noteEditor: NoteEditorComponent;

    constructor(
        public http: HttpClient,
        private notify: NotificationService,
        public dialogRef: MatDialogRef<SendAlfrescoActionComponent>,
        @Inject(MAT_DIALOG_DATA) public data: any,
        public functions: FunctionsService
    ) { }

    async ngOnInit(): Promise<void> {
        this.loading = true;
        await this.checkAlfresco();
        this.loading = false;
        this.initTree();

        this.searchFolder.valueChanges
            .pipe(
                debounceTime(300),
                tap((value: any) => {
                    this.selectedFolder = null;
                    if (value.length === 0) {
                        $j('#jstreeAlfresco').jstree(true).settings.core.data =
                        {
                            'url': (node: any) => {
                                return node.id === '#' ?
                                    '../../rest/alfresco/rootFolders' : `../../rest/alfresco/folders/${node.id}/children`;
                            },
                            'data': (node: any) => {
                                return { 'id': node.id };
                            }
                        }
                        $j('#jstreeAlfresco').jstree("refresh");
                    }
                }),
                filter(value => value.length > 2),
                tap((data: any) => {

                    $j('#jstreeAlfresco').jstree(true).settings.core.data =
                    {
                        'url': (node: any) => {
                            return node.id === '#' ?
                                `../../rest/alfresco/autocomplete/folders?search=${data}` : `../../rest/alfresco/folders/${node.id}/children`;
                        },
                        'data': (node: any) => {
                            return { 'id': node.id };
                        }
                    }
                    $j('#jstreeAlfresco').jstree("refresh");
                })
            ).subscribe();
    }

    checkAlfresco() {
        this.resourcesErrors = [];

        return new Promise((resolve, reject) => {
            this.http.post('../../rest/resourcesList/users/' + this.data.userId + '/groups/' + this.data.groupId + '/baskets/' + this.data.basketId + '/actions/' + this.data.action.id + '/checkSendAlfresco', { resources: this.data.resIds })
                .subscribe((data: any) => {

                    if(!this.functions.empty(data.resourcesInformations.error)) {
                        this.resourcesErrors = data.resourcesInformations.error;
                        this.noResourceToProcess = this.resourcesErrors.length === this.data.resIds.length;
                    }
                    if(!this.functions.empty(data.resourcesInformations.fatalError)) {
                        this.notify.error(this.lang[data.resourcesInformations.fatalError.reason]);
                        this.dialogRef.close();
                    }
                    resolve(true);
                }, (err: any) => {
                    this.notify.handleSoftErrors(err);
                    this.dialogRef.close();
                });
        });
    }

    getRootAlfrescoFolders() {
        return new Promise((resolve, reject) => {
            this.http.get(`../../rest/alfresco/rootFolders`).pipe(
                map((data: any) => {
                    data.folders = data.folders.map((folder: any) => {
                        return {
                            ...folder,
                            icon: 'fa fa-folder',
                            parent: '#',
                            text: folder.name,
                            children: true
                        }
                    });
                    return data.folders;
                }),
                tap((folders: any) => {
                    this.alfrescoFolders = folders;
                    resolve(true);
                }),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    this.dialogRef.close();
                    return of(false);
                })
            ).subscribe()
        });
    }

    getAlfrescoFolder(folderId: string) {
        this.http.get(`../../rest/folders/${folderId}/children`).pipe(
            map((data: any) => {
                data.folders = data.folders.map((folder: any) => {
                    return {
                        ...folder,
                        id: folder.id,
                        icon: 'fa fa-folder',
                        text: folder.name,
                        parent: '#',
                        children: true
                    }
                });
                return data.folders;
            }),
            tap((folders: any) => {
                this.alfrescoFolders = folders;
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe()
    }

    initTree() {
        setTimeout(() => {
            $j('#jstreeAlfresco').jstree({
                "checkbox": {
                    'deselect_all': true,
                    "three_state": false //no cascade selection
                },
                'core': {
                    force_text: true,
                    'themes': {
                        'name': 'proton',
                        'responsive': true
                    },
                    'multiple': false,
                    'data': {
                        'url': (node: any) => {
                            return node.id === '#' ?
                                '../../rest/alfresco/rootFolders' : `../../rest/alfresco/folders/${node.id}/children`;
                        },
                        'data': (node: any) => {
                            return { 'id': node.id };
                        },
                        /*"dataFilter": (data: any) => {

                            let objFold = JSON.parse(data);
                            objFold = objFold.folders;

                            return JSON.stringify(objFold);
                        },*/
                        /*"success": (data: any) => {
                            data.folders = data.folders.map((folder: any) => {
                                return {
                                    ...folder,
                                    id: folder.id,
                                    icon: 'fa fa-folder',
                                    text: folder.name,
                                    parent: '#',
                                    children: true
                                }
                            });
                            console.log(data.folders);
                            return data.folders;
                        }*/
                    },

                    //'data': this.alfrescoFolders,
                },
                "plugins": ["checkbox"]
            });
            $j('#jstreeAlfresco')
                // listen for event
                .on('select_node.jstree', (e: any, data: any) => {
                    this.selectedFolder = data.node.id;

                }).on('deselect_node.jstree', (e: any, data: any) => {
                    this.selectedFolder = null;
                })
                // create the instance
                .jstree();
        }, 0);
    }

    onSubmit() {
        this.loading = true;

        if (this.data.resIds.length > 0) {
            this.executeAction();
        }
    }

    executeAction() {

        const realResSelected: number[] = this.data.resIds.filter((resId: any) => this.resourcesErrors.map(resErr => resErr.res_id).indexOf(resId) === -1);
        
        this.http.put(this.data.processActionRoute, { resources: realResSelected, note: this.noteEditor.getNoteContent(), data: { folderId: this.selectedFolder } }).pipe(
            tap((data: any) => {
                if (!data) {
                    this.dialogRef.close('success');
                }
                if (data && data.errors != null) {
                    this.notify.error(data.errors);
                }
            }),
            finalize(() => this.loading = false),
            catchError((err: any) => {
                this.notify.handleErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    isValidAction() {
        if (this.selectedFolder !== null && !this.noResourceToProcess) {
            return true;
        } else {
            return false;
        }
    }
}
