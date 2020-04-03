import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { HeaderService } from '../../../service/header.service';
import { AppService } from '../../../service/app.service';
import { FormControl, Validators, FormGroup } from '@angular/forms';
import { finalize, tap, catchError, filter, exhaustMap } from 'rxjs/operators';
import { of } from 'rxjs';
import { FunctionsService } from '../../../service/functions.service';
import { ConfirmComponent } from '../../../plugins/modal/confirm.component';
import { MatDialog } from '@angular/material/dialog';

@Component({
    templateUrl: "tag-administration.component.html",
    providers: [AppService]
})
export class TagAdministrationComponent implements OnInit {

    id: string;
    creationMode: boolean;
    lang: any = LANG;
    loading: boolean = false;
    loadingTags: boolean = true;

    tags: any[] = [];

    tag: any = {
        label: new FormControl({ value: '', disabled: false }, [Validators.required]),
        description: new FormControl({ value: '', disabled: false }),
        usage: new FormControl({ value: '', disabled: false }),
        canMerge: new FormControl({ value: true, disabled: false }),
        countResources: new FormControl({ value: 0, disabled: false })
    };

    selectMergeTag = new FormControl({ value: '', disabled: false });

    tagFormGroup = new FormGroup(this.tag);

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

    ngOnInit(): void {
        this.loading = true;
        
        this.route.params.subscribe((params) => {
            if (typeof params['id'] == "undefined") {
                this.headerService.setHeader(this.lang.tagCreation);
                this.creationMode = true;
                this.loading = false;
            } else {
                this.creationMode = false;
                this.id = params['id'];
                this.http.get("../../rest/tags/" + this.id).pipe(
                    tap((data: any) => {
                        this.getTags();
                        Object.keys(this.tag).forEach(element => {
                            if (!this.functions.empty(data[element])) {
                                this.tag[element].setValue(data[element]);
                            }
                        });
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
        this.http.post(`../../rest/tags`, this.formatTag()).pipe(
            tap(() => {
                this.notify.success(this.lang.tagAdded);
                this.router.navigate(["/administration/tags"]);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    updateTag() {
        this.http.put(`../../rest/tags/${this.id}`, this.formatTag()).pipe(
            tap(() => {
                this.notify.success(this.lang.tagUpdated);
                this.router.navigate(["/administration/tags"]);
            }),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe();
    }

    getTags() {
        this.http.get('../../rest/tags').pipe(
            tap((data: any) => {                
                this.tags = data.tags.filter((tag: any) => tag.id != this.id).map((tag: any) => {
                    return {
                        id: tag.id,
                        label: tag.label,
                        countResources: tag.countResources
                    }
                });
            }),
            finalize(() => this.loadingTags = false),
            catchError((err: any) => {
                this.notify.handleSoftErrors(err);
                return of(false);
            })
        ).subscribe()
    }

    mergeTag(tagId: any) {
        this.selectMergeTag.reset();
        const selectedTag = this.tags.filter(tag => tag.id === tagId)[0];

        const dialogMessage = `${this.lang.confirmAction}<br/><br/>${this.lang.theTag}<b> "${this.tag.label.value}" </b>${this.lang.willBeDeletedAndMerged}<b> "${selectedTag.label}"</b><br/><br/>${this.lang.willBeTransferredToNewTag}<b> "${selectedTag.label}"</b> : <b>${this.tag.countResources.value}</b>`;

        const dialogRef = this.dialog.open(ConfirmComponent, { panelClass: 'maarch-modal', autoFocus: false, disableClose: true, data: { title: `${this.lang.mergeWith}  "${selectedTag.label}"`, msg: dialogMessage } });
        dialogRef.afterClosed().pipe(
            filter((data: string) => data === 'ok'),
            exhaustMap(() => this.http.put(`../../rest/mergeTags`, { idMaster: selectedTag.id, idMerge: this.id })),
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
}
