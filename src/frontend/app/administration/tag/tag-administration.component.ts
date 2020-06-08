import { Component, OnInit, ElementRef, ViewChild } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import { FormControl, Validators, FormGroup } from '@angular/forms';
import { finalize, tap, catchError, filter, exhaustMap, map, startWith } from 'rxjs/operators';
import { of, Observable } from 'rxjs';
import { FunctionsService } from '../../../service/functions.service';
import { ConfirmComponent } from '../../../plugins/modal/confirm.component';
import { MatDialog } from '@angular/material/dialog';
import { MatChipInputEvent } from '@angular/material/chips';
import { MatAutocompleteSelectedEvent } from '@angular/material/autocomplete';

@Component({
    templateUrl: 'tag-administration.component.html',
    providers: [AppService]
})
export class TagAdministrationComponent implements OnInit {

    id: string;
    creationMode: boolean;
    lang: any = LANG;
    loading: boolean = false;
    loadingTags: boolean = true;
    advancedMode: boolean = false;

    tags: any[] = [];

    tag: any = {
        label: new FormControl({ value: '', disabled: false }, [Validators.required]),
        description: new FormControl({ value: '', disabled: false }),
        parentId: new FormControl({ value: '', disabled: false }),
        links: new FormControl({ value: [], disabled: false }),
        usage: new FormControl({ value: '', disabled: false }),
        canMerge: new FormControl({ value: true, disabled: false }),
        countResources: new FormControl({ value: 0, disabled: false })
    };
    myControl = new FormControl();
    filteredOptions: Observable<string[]>;

    selectMergeTag = new FormControl({ value: '', disabled: false });

    tagFormGroup = new FormGroup(this.tag);

    @ViewChild('linkedTagInput') linkedTagInput: ElementRef<HTMLInputElement>;

    constructor(
        public http: HttpClient,
        private route: ActivatedRoute,
        private router: Router,
        private notify: NotificationService,
        private headerService: HeaderService,
        public appService: AppService,
        public functions: FunctionsService,
        public dialog: MatDialog
    ) {
    }

    async ngOnInit(): Promise<void> {
        this.loading = true;

        await this.getTags();

        this.route.params.subscribe((params) => {
            if (typeof params['id'] === 'undefined') {
                this.headerService.setHeader(this.lang.tagCreation);
                this.creationMode = true;
                this.loading = false;
            } else {
                this.creationMode = false;
                this.id = params['id'];
                this.http.get(`../rest/tags/${this.id}`).pipe(
                    tap((data: any) => {
                        Object.keys(this.tag).forEach(element => {
                            if (!this.functions.empty(data[element])) {
                                this.tag[element].setValue(data[element]);
                            }
                        });

                        if (!this.functions.empty(this.tag.parentId.value)) {
                            this.toggleAdvancedTag();
                        }

                        this.headerService.setHeader(this.lang.tagModification, this.tag.label.value);
                    }),
                    finalize(() => this.loading = false),
                    catchError((err: any) => {
                        this.notify.handleErrors(err);
                        return of(false);
                    })
                ).subscribe();
            }
        });
    }

    onSubmit() {
        if (this.creationMode) {
            this.createTag();
        } else {
            this.updateTag();
        }
    }

    formatTag() {
        const formattedTag = {};
        Object.keys(this.tag).forEach(element => {
            formattedTag[element] = this.tag[element].value;
        });

        return formattedTag;
    }

    createTag() {
        this.http.post(`../rest/tags`, this.formatTag()).pipe(
            tap(() => {
                this.notify.success(this.lang.tagAdded);
                this.router.navigate(['/administration/tags']);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    updateTag() {

        this.http.put(`../rest/tags/${this.id}`, this.formatTag()).pipe(
            tap(() => {
                this.notify.success(this.lang.tagUpdated);
                this.router.navigate(['/administration/tags']);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    getTags() {
        return new Promise((resolve) => {
            this.http.get('../rest/tags').pipe(
                tap((data: any) => {
                    this.tags = data.tags.filter((tag: any) => tag.id !== this.id).map((tag: any) => {
                        return {
                            id: tag.id,
                            label: tag.label,
                            parentId : tag.parentId,
                            countResources: tag.countResources
                        };
                    });
                    resolve(true);
                }),
                finalize(() => this.loadingTags = false),
                catchError((err: any) => {
                    this.notify.handleSoftErrors(err);
                    return of(false);
                })
            ).subscribe();
        });
    }

    mergeTag(tagId: any) {
        this.selectMergeTag.reset();
        const selectedTag = this.tags.filter(tag => tag.id === tagId)[0];

        const dialogMessage = `${this.lang.confirmAction}<br/><br/>${this.lang.theTag}<b> "${this.tag.label.value}" </b>${this.lang.willBeDeletedAndMerged}<b> "${selectedTag.label}"</b><br/><br/>${this.lang.willBeTransferredToNewTag}<b> "${selectedTag.label}"</b> : <b>${this.tag.countResources.value}</b>`;

        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: `${this.lang.mergeWith}  "${selectedTag.label}"`, msg: dialogMessage } });
        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.put(`../rest/mergeTags`, { idMaster: selectedTag.id, idMerge: this.id })),
            tap(() => {
                this.notify.success(this.lang.tagMerged);
                this.router.navigate([`/administration/tags/${selectedTag.id}`]);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    async toggleAdvancedTag() {
        this.advancedMode = !this.advancedMode;
        this.getTagsTree();
        this.filteredOptions = this.myControl.valueChanges.pipe(
            startWith(null),
            map((fruit: string | null) => fruit ? this._filter(fruit) : this.tags.slice()));
    }

    private _filter(value: string): string[] {
        let filterValue = value;
        if (typeof value === 'string') {
            filterValue = value.toLowerCase();
        }
        return this.tags.filter(tag => tag.label.toLowerCase().indexOf(filterValue) > -1);
    }

    isSelected(tag: any) {
        return this.tag.links.value.filter((tagItem: any) => tagItem == tag.id).length > 0;
    }

    remove(tag: string): void {
        const index = this.tag.links.value.indexOf(tag);

        if (index >= 0) {
            this.tag.links.value.splice(index, 1);
        }
    }

    selected(event: MatAutocompleteSelectedEvent): void {
        const tmpArr = this.tag.links.value;
        tmpArr.push(event.option.value.id);

        this.tag.links.setValue(tmpArr);
        this.linkedTagInput.nativeElement.value = '';
        this.myControl.setValue(null);
    }

    getTagsTree() {
        const tagsTree = this.tags.filter((tag: any) => tag.id != this.id).map((tag: any) => {
            if (this.tag.parentId.value == tag.id) {
                return {
                    id: tag.id,
                    text: tag.label,
                    parent: this.functions.empty(tag.parentId) ? '#' : tag.parentId,
                    state: {
                        opened: true,
                        selected: true
                    }
                };
            } else {
                return {
                    id: tag.id,
                    text: tag.label,
                    parent: this.functions.empty(tag.parentId) ? '#' : tag.parentId,
                };
            }
        });

        setTimeout(() => {
            $('#jstree')
                .on('select_node.jstree', (e: any, item: any) => {
                    this.tag.parentId.setValue(parseInt(item.node.id));
                })
                .jstree({
                    'checkbox': {
                        'deselect_all': true,
                        'three_state': false // no cascade selection
                    },
                    'core': {
                        force_text: true,
                        'themes': {
                            'name': 'proton',
                            'responsive': true
                        },
                        'multiple': false,
                        'data': tagsTree
                    },
                    'plugins': ['checkbox', 'search', 'sort']
                });
            let to: any = false;
            $('#jstree_search').keyup(function () {
                if (to) { clearTimeout(to); }
                to = setTimeout(function () {
                    const v: any = $('#jstree_search').val();
                    $('#jstree').jstree(true).search(v);
                }, 250);
            });
        }, 0);
    }

    getTagLabel(id: any) {
        return this.tags.filter((tag: any) => tag.id == id)[0].label;
    }
}
