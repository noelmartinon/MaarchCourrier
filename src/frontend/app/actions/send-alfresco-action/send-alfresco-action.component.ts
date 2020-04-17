import { Component, OnInit, Inject, ViewChild } from '@angular/core';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MAT_DIALOG_DATA, MatDialogRef } from '@angular/material/dialog';
import { HttpClient } from '@angular/common/http';
import { NoteEditorComponent } from '../../notes/note-editor.component';
import { tap, finalize, catchError, debounceTime, filter } from 'rxjs/operators';
import { of } from 'rxjs';
import { FormControl } from '@angular/forms';
import { FunctionsService } from '../../../service/functions.service';

declare var $: any;

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
    selectedFolderName: string = null;

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
                    this.selectedFolderName = null;
                    if (value.length === 0) {
                        $('#jstreeAlfresco').jstree(true).settings.core.data =
                            {
                                'url': (node: any) => {
                                    return node.id === '#' ?
                                        '../rest/alfresco/rootFolders' : `../rest/alfresco/folders/${node.id}/children`;
                                },
                                'data': (node: any) => {
                                    return { 'id': node.id };
                                }
                            };
                        $('#jstreeAlfresco').jstree("refresh");
                    }
                }),
                filter(value => value.length > 2),
                tap((data: any) => {

                    $('#jstreeAlfresco').jstree(true).settings.core.data =
                        {
                            'url': (node: any) => {
                                return node.id === '#' ?
                                    `../rest/alfresco/autocomplete/folders?search=${data}` : `../rest/alfresco/folders/${node.id}/children`;
                            },
                            'data': (node: any) => {
                                return { 'id': node.id };
                            }
                        };
                    $('#jstreeAlfresco').jstree("refresh");
                })
            ).subscribe();
    }

    checkAlfresco() {
        this.resourcesErrors = [];

        return new Promise((resolve, reject) => {
            this.http.post('../rest/resourcesList/users/' + this.data.userId + '/groups/' + this.data.groupId + '/baskets/' + this.data.basketId + '/actions/' + this.data.action.id + '/checkSendAlfresco', { resources: this.data.resIds })
                .subscribe((data: any) => {
                    if(!this.functions.empty(data.fatalError)) {
                        this.notify.error(this.lang[data.reason]);
                        this.dialogRef.close();
                    } else if(!this.functions.empty(data.resourcesInformations.error)) {
                        this.resourcesErrors = data.resourcesInformations.error;
                        this.noResourceToProcess = this.resourcesErrors.length === this.data.resIds.length;
                    }
                    resolve(true);
                }, (err: any) => {
                    this.notify.handleSoftErrors(err);
                    this.dialogRef.close();
                });
        });
    }

    initTree() {
        setTimeout(() => {
            $('#jstreeAlfresco').jstree({
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
                                '../rest/alfresco/rootFolders' : `../rest/alfresco/folders/${node.id}/children`;
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
            $('#jstreeAlfresco')
            // listen for event
                .on('select_node.jstree', (e: any, data: any) => {
                    this.selectedFolder = data.node.id;
                    this.selectedFolderName = this.getNameWithParents(data.node.text, data.node.parent);
                }).on('deselect_node.jstree', (e: any, data: any) => {
                this.selectedFolder = null;
                this.selectedFolderName = null;
            })
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

        this.http.put(this.data.processActionRoute, { resources: realResSelected, note: this.noteEditor.getNoteContent(), data: { folderId: this.selectedFolder, folderName: this.selectedFolderName } }).pipe(
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
        return this.selectedFolder !== null && !this.noResourceToProcess;
    }

    getNameWithParents(name: string, parentId: string) {
        if (parentId === '#') {
            return name;
        }
        $('#jstreeAlfresco').jstree(true).get_json('#', {flat:true}).forEach((folder: any) => {
            if (folder.id == parentId) {
                name = folder.text + "/" + name;
                if (folder.parent != '#') {
                    name = this.getNameWithParents(name, folder.parent);
                }
            }
        });

        return name;
    }
}
