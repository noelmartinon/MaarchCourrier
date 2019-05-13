import { ChangeDetectorRef, Component, OnInit, NgZone, ViewChild, Inject } from '@angular/core';
import { MediaMatcher } from '@angular/cdk/layout';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { LANG } from '../../translate.component';
import { NotificationService } from '../../notification.service';
import { MatSidenav, MatDialog, MatDialogRef, MAT_DIALOG_DATA } from '@angular/material';
import { HeaderService } from '../../../service/header.service';

declare function $j(selector: any): any;
declare var tinymce: any;
declare var angularGlobals: any;


@Component({
    templateUrl: "template-administration.component.html",
    styleUrls: ['template-administration.component.scss'],
    providers: [NotificationService]
})
export class TemplateAdministrationComponent implements OnInit {

    /*HEADER*/
    @ViewChild('snav') public  sidenavLeft   : MatSidenav;
    @ViewChild('snav2') public sidenavRight  : MatSidenav;

    private _mobileQueryListener    : () => void;
    mobileQuery                     : MediaQueryList;

    coreUrl                 : string;
    lang                    : any = LANG;
    loading                 : boolean = false;

    creationMode            : boolean;
    template                : any       = {};
    statuses                : any[]     = [];
    categoriesList          : any[]     = [];
    keywordsList            : any[]     = [];
    defaultTemplatesList    : any;
    attachmentTypesList     : any;
    datasourcesList         : any;
    jnlpValue               : any       = {};
    extensionModels         : any[]     = [];
    buttonFileName          : any       = this.lang.importFile;
    lockFound               : boolean   = false;
    intervalLockFile        : any;

    dialogRef               : MatDialogRef<any>;
    data                    : any[]      = [];
    config                  : any        = {};


    constructor(changeDetectorRef: ChangeDetectorRef, media: MediaMatcher, public http: HttpClient, private zone: NgZone, private route: ActivatedRoute, private router: Router, private notify: NotificationService, private headerService: HeaderService, public dialog: MatDialog) {
        $j("link[href='merged_css.php']").remove();
        this.mobileQuery = media.matchMedia('(max-width: 768px)');
        this._mobileQueryListener = () => changeDetectorRef.detectChanges();
        this.mobileQuery.addListener(this._mobileQueryListener);
        window['angularTemplateComponent'] = {
            componentAfterUpload: (base64Content: any) => this.processAfterUpload(base64Content)
        };
    }

    ngOnDestroy(): void {
        this.mobileQuery.removeListener(this._mobileQueryListener);
    }

    ngOnInit(): void {
        this.coreUrl = angularGlobals.coreUrl;
        this.loading = true;

        this.route.params.subscribe(params => {
            if (typeof params['id'] == "undefined") {
                this.headerService.setHeader(this.lang.templateCreation);
                window['MainHeaderComponent'].setSnav(this.sidenavLeft);
                window['MainHeaderComponent'].setSnavRight(this.sidenavRight);

                this.creationMode = true;

                this.http.get(this.coreUrl + 'rest/administration/templates/new')
                    .subscribe((data: any) => {
                        this.setInitialValue(data);
                        this.template.template_target = '';
                        this.template.template_type = 'OFFICE';
                        this.loading = false;

                    });
            } else {
                window['MainHeaderComponent'].setSnav(this.sidenavLeft);
                window['MainHeaderComponent'].setSnavRight(this.sidenavRight);

                this.creationMode = false;
                this.http.get(this.coreUrl + 'rest/templates/' + params['id'] + '/details')
                    .subscribe((data: any) => {
                        this.setInitialValue(data);
                        this.template = data.template;
                        this.headerService.setHeader(this.lang.templateModification, this.template.template_label);
                        this.loading = false;
                        if (this.template.template_type == 'HTML') {
                            this.initMce('textarea#templateHtml');
                        }
                        if (this.template.template_type == 'OFFICE_HTML') {
                            this.initMce('textarea#templateOfficeHtml');
                        }
                        if (this.template.template_style == '' && this.template.template_file_name != null) {
                            this.buttonFileName = this.template.template_file_name;
                        } else if (this.template.template_style != '') {
                            this.buttonFileName = this.template.template_style;
                        }

                        if (this.template.template_style == '') {
                            this.template.template_style = 'uploadFile';
                        }
                    });
            }
            if (!this.template.template_attachment_type) {
                this.template.template_attachment_type = 'all';
            }
        });
    }

    initMce(selectorId: string) {
        setTimeout(() => {
            tinymce.remove('textarea');
            //LOAD EDITOR TINYMCE for MAIL SIGN
            tinymce.baseURL = "../../node_modules/tinymce";
            tinymce.suffix = '.min';
            tinymce.init({
                selector: selectorId,
                statusbar: false,
                language: "fr_FR",
                language_url: "tools/tinymce/langs/fr_FR.js",
                height: "200",
                plugins: [
                    "textcolor",
                    "autoresize",
                    "code"
                ],
                external_plugins: {
                    'bdesk_photo': "../../apps/maarch_entreprise/tools/tinymce/bdesk_photo/plugin.min.js"
                },
                menubar: false,
                toolbar: "undo | bold italic underline | alignleft aligncenter alignright | bdesk_photo | forecolor | code",
                theme_buttons1_add: "fontselect,fontsizeselect",
                theme_buttons2_add_before: "cut,copy,paste,pastetext,pasteword,separator,search,replace,separator",
                theme_buttons2_add: "separator,insertdate,inserttime,preview,separator,forecolor,backcolor",
                theme_buttons3_add_before: "tablecontrols,separator",
                theme_buttons3_add: "separator,print,separator,ltr,rtl,separator,fullscreen,separator,insertlayer,moveforward,movebackward,absolut",
                theme_toolbar_align: "left",
                theme_advanced_toolbar_location: "top",
                theme_styles: "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1",
                setup: (ed :any) => {
                    ed.on('keyup', (e: any) => {
                        if (this.template.template_type == 'HTML' && tinymce.get('templateHtml') != null) {
                            this.template.template_content = tinymce.get('templateHtml').getContent();
                        }
                        if (this.template.template_type == 'OFFICE_HTML' && tinymce.get('templateOfficeHtml') != null) {
                            this.template.template_content = tinymce.get('templateOfficeHtml').getContent();
                        }
                    });
                }
                
            });
        }, 20);
    }

    setInitialValue(data: any) {
        this.extensionModels = [];
        data.templatesModels.forEach((model: any) => {
            if (this.extensionModels.indexOf(model.fileExt) == -1) {
                this.extensionModels.push(model.fileExt);
            }
        });
        this.defaultTemplatesList = data.templatesModels;

        this.attachmentTypesList = data.attachmentTypes;
        this.datasourcesList = data.datasources;
        setTimeout(() => {
            $j('#jstree')
                .on('select_node.jstree', function (e: any, data: any) {
                    if (data.event) {
                        data.instance.select_node(data.node.children_d);
                    }
                })
                .jstree({
                    "checkbox": { three_state: false },
                    'core': {
                        'themes': {
                            'name': 'proton',
                            'responsive': true
                        },
                        'data': data.entities
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
        }, 0);
    }

    clickOnUploader(id: string) {
        $j('#' + id).click();
    }

    uploadFileTrigger(fileInput: any) {
        this.template.jnlpUniqueId = null;
        if (fileInput.target.files && fileInput.target.files[0]) {
            this.template.uploadedFile = {};
            this.template.uploadedFile.name = fileInput.target.files[0].name;
            this.template.uploadedFile.size = fileInput.target.files[0].size;
            this.template.uploadedFile.type = fileInput.target.files[0].type;
            if (this.template.uploadedFile.label == "") {
                this.template.uploadedFile.label = this.template.uploadedFile.name;
            }

            var reader = new FileReader();
            reader.readAsDataURL(fileInput.target.files[0]);

            reader.onload = function (value: any) {
                window['angularTemplateComponent'].componentAfterUpload(value.target.result);
            };
        }
    }

    processAfterUpload(b64Content: any) {
        this.zone.run(() => this.resfreshUpload(b64Content));
    }

    resfreshUpload(b64Content: any) {
        this.template.uploadedFile.base64 = b64Content.replace(/^data:.*?;base64,/, "");
        this.template.template_style = 'uploadFile';
        this.fileImported();
    }

    startJnlp() {
        if (this.creationMode || (this.template.template_file_name == null && this.template.template_path == null)) {
            this.jnlpValue.objectType = 'templateCreation';
            for (let element of this.defaultTemplatesList) {
                if (this.template.template_style == element.fileExt + ': ' + element.fileName) {
                    this.jnlpValue.objectId = element.filePath;
                }
            }
        } else {
            this.jnlpValue.objectType = 'templateModification';
            this.jnlpValue.objectId = this.template.template_id;
        }
        this.jnlpValue.table = 'templates';
        this.jnlpValue.uniqueId = 0;
        this.jnlpValue.cookies = document.cookie;

        this.http.post(this.coreUrl + 'rest/jnlp', this.jnlpValue)
            .subscribe((data: any) => {
                this.template.jnlpUniqueId = data.jnlpUniqueId;
                this.fileToImport();
                window.location.href = this.coreUrl + 'rest/jnlp/' + data.generatedJnlp;
                this.checkLockFile();
            }, (err) => {
                this.notify.error(err.error.errors);
            });
    }

    checkLockFile() {
        this.intervalLockFile = setInterval(() => {
            this.http.get(this.coreUrl + 'rest/jnlp/lock/' + this.template.jnlpUniqueId)
                .subscribe((data: any) => {
                    this.lockFound = data.lockFileFound;
                    if (!this.lockFound) {
                        clearInterval(this.intervalLockFile);
                    }
                });
        }, 1000)
    }

    duplicateTemplate() {
        if (!this.lockFound && this.template.template_target != 'acknowledgementReceipt') {
            let r = confirm(this.lang.confirmDuplicate);

            if (r) {
                this.http.post(this.coreUrl + 'rest/templates/' + this.template.template_id + '/duplicate', { 'id': this.template.template_id })
                    .subscribe((data: any) => {
                        this.notify.success(this.lang.templateDuplicated);
                        this.router.navigate(['/administration/templates/' + data.id]);
                    }, (err) => {
                        this.notify.error(err.error.errors);
                    });
            }
        }
    }

    onSubmit() {
        this.template.entities = $j('#jstree').jstree(true).get_checked();
        if (this.template.template_target != 'notifications') {
            this.template.template_datasource = 'letterbox_attachment';
        }

        if (this.template.template_type == 'HTML') {
            this.template.template_content = tinymce.get('templateHtml').getContent();

        } else if (this.template.template_type == 'OFFICE_HTML') {
            this.template.template_content = tinymce.get('templateOfficeHtml').getContent();
        }

        if (this.template.template_target == 'acknowledgementReceipt') {
            if (this.template.template_content == '' && (!this.template.template_style || (this.template.template_style && !this.template.jnlpUniqueId && !this.template.uploadedFile && !this.template.template_file_name))) {
                alert(this.lang.mustCompleteAR);
                return;
            }
        } else if (this.template.template_target != 'acknowledgementReceipt' && this.template.template_type == 'OFFICE') {
            if (!this.template.template_style || (this.template.template_style && !this.template.jnlpUniqueId && !this.template.template_file_name)) {
                alert(this.lang.editModelFirst);
                return;
            }
        }
        
        if (this.creationMode) {
            if (this.template.template_style == 'uploadFile') {
                this.template.template_style = '';
            }
            this.http.post(this.coreUrl + 'rest/templates', this.template)
                .subscribe((data: any) => {
                    if (data.checkEntities) {
                        this.config = {
                            data: {
                                entitiesList: data.checkEntities,
                                template_attachment_type: this.template.template_attachment_type
                            }
                        };
                        this.dialogRef = this.dialog.open(TemplateAdministrationCheckEntitiesModalComponent, this.config);
                    } else {
                        this.router.navigate(['/administration/templates']);
                        this.notify.success(this.lang.templateAdded);
                    }
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        } else {
            if (this.template.template_style == 'uploadFile') {
                this.template.template_style = '';
            }
            this.http.put(this.coreUrl + 'rest/templates/' + this.template.template_id, this.template)
                .subscribe((data: any) => {
                    if (data.checkEntities) {
                        this.config = {
                            data: {
                                entitiesList: data.checkEntities,
                                template_attachment_type: this.template.template_attachment_type
                            }
                        };
                        this.dialogRef = this.dialog.open(TemplateAdministrationCheckEntitiesModalComponent, this.config);
                    } else {
                        this.router.navigate(['/administration/templates']);
                        this.notify.success(this.lang.templateUpdated);
                    }
                }, (err) => {
                    this.notify.error(err.error.errors);
                });
        }
    }

    checkArMode() {
        if (this.template.template_content == '' && !this.template.template_style && this.template.target === 'acknowledgementReceipt') {
            alert(this.lang.mustCompleteAR);
            return true;
        } else {
            return false;
        }
    }

    displayDatasources(datasource: any) {
        if (datasource.target == 'notification' && this.template.template_target == 'notifications') {
            return true;
        } else if (datasource.target == 'document' && this.template.template_target != 'notifications') {
            return true;
        }
        return false;
    }

    updateTemplateType() {
        if (this.template.template_target == 'attachments') {
            this.template.template_type = 'OFFICE';
        } else if (this.template.template_target == 'notifications' || this.template.template_target == 'doctypes' || this.template.template_target == 'sendmail') {
            this.template.template_type = 'HTML';
            this.initMce('textarea#templateHtml');
        } else if (this.template.template_target == 'notes') {
            this.template.template_type = 'TXT';
        } else if (this.template.template_target == 'acknowledgementReceipt') {
            this.template.template_type = 'OFFICE_HTML';
            this.template.template_attachment_type = '';
            this.initMce('textarea#templateOfficeHtml');
        }
    }

    fileImported() {
        this.buttonFileName = this.template.uploadedFile.name;
    }

    fileToImport() {
        this.buttonFileName = this.lang.importFile;
    }

    resetFileUploaded() {
        this.fileToImport();
        this.template.uploadedFile = null;
    }

    loadTab(event: any) {
        if (event.index === 0) {
            this.initMce('textarea#templateOfficeHtml');
        } else {
            if (this.template.template_file_name == null && this.template.template_style == null) {
                this.buttonFileName = this.lang.importFile;
            }
        }
    }
}
@Component({
    templateUrl: "template-administration-checkEntities-modal.component.html",
    styleUrls: ['template-administration-checkEntities-modal.scss']
})
export class TemplateAdministrationCheckEntitiesModalComponent {
    lang: any = LANG;

    constructor(public http: HttpClient, @Inject(MAT_DIALOG_DATA) public data: any, public dialogRef: MatDialogRef<TemplateAdministrationCheckEntitiesModalComponent>) {
    }

    ngOnInit(): void { }

}
