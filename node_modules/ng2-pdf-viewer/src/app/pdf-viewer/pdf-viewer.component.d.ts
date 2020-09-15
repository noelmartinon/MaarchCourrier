/**
 * Created by vadimdez on 21/06/16.
 */
import { ElementRef, EventEmitter, OnChanges, SimpleChanges, OnInit, OnDestroy, AfterViewChecked } from '@angular/core';
import { PDFDocumentProxy, PDFSource, PDFProgressData } from 'pdfjs-dist';
import * as ɵngcc0 from '@angular/core';
export declare enum RenderTextMode {
    DISABLED = 0,
    ENABLED = 1,
    ENHANCED = 2
}
export declare class PdfViewerComponent implements OnChanges, OnInit, OnDestroy, AfterViewChecked {
    private element;
    pdfViewerContainer: any;
    private isVisible;
    static CSS_UNITS: number;
    static BORDER_WIDTH: number;
    private pdfMultiPageViewer;
    private pdfMultiPageLinkService;
    private pdfMultiPageFindController;
    private pdfSinglePageViewer;
    private pdfSinglePageLinkService;
    private pdfSinglePageFindController;
    private _cMapsUrl;
    private _renderText;
    private _renderTextMode;
    private _stickToPage;
    private _originalSize;
    private _pdf;
    private _page;
    private _zoom;
    private _zoomScale;
    private _rotation;
    private _showAll;
    private _canAutoResize;
    private _fitToPage;
    private _externalLinkTarget;
    private _showBorders;
    private lastLoaded;
    private _latestScrolledPage;
    private resizeTimeout;
    private pageScrollTimeout;
    private isInitialized;
    private loadingTask;
    afterLoadComplete: EventEmitter<PDFDocumentProxy>;
    pageRendered: EventEmitter<CustomEvent<any>>;
    textLayerRendered: EventEmitter<CustomEvent<any>>;
    onError: EventEmitter<any>;
    onProgress: EventEmitter<PDFProgressData>;
    pageChange: EventEmitter<number>;
    src: string | Uint8Array | PDFSource;
    cMapsUrl: string;
    page: any;
    renderText: boolean;
    renderTextMode: RenderTextMode;
    originalSize: boolean;
    showAll: boolean;
    stickToPage: boolean;
    zoom: number;
    zoomScale: 'page-height' | 'page-fit' | 'page-width';
    rotation: number;
    externalLinkTarget: string;
    autoresize: boolean;
    fitToPage: boolean;
    showBorders: boolean;
    static getLinkTarget(type: string): any;
    static setExternalLinkTarget(type: string): void;
    constructor(element: ElementRef);
    ngAfterViewChecked(): void;
    ngOnInit(): void;
    ngOnDestroy(): void;
    onPageResize(): void;
    readonly pdfLinkService: any;
    readonly pdfViewer: any;
    readonly pdfFindController: any;
    ngOnChanges(changes: SimpleChanges): void;
    updateSize(): void;
    clear(): void;
    private setupMultiPageViewer;
    private setupSinglePageViewer;
    private getValidPageNumber;
    private getDocumentParams;
    private loadPDF;
    private update;
    private render;
    private getScale;
    private getCurrentViewer;
    private resetPdfDocument;
    static ɵfac: ɵngcc0.ɵɵFactoryDef<PdfViewerComponent, never>;
    static ɵcmp: ɵngcc0.ɵɵComponentDefWithMeta<PdfViewerComponent, "pdf-viewer", never, { "cMapsUrl": "c-maps-url"; "page": "page"; "renderText": "render-text"; "renderTextMode": "render-text-mode"; "originalSize": "original-size"; "showAll": "show-all"; "stickToPage": "stick-to-page"; "zoom": "zoom"; "zoomScale": "zoom-scale"; "rotation": "rotation"; "externalLinkTarget": "external-link-target"; "autoresize": "autoresize"; "fitToPage": "fit-to-page"; "showBorders": "show-borders"; "src": "src"; }, { "afterLoadComplete": "after-load-complete"; "pageRendered": "page-rendered"; "textLayerRendered": "text-layer-rendered"; "onError": "error"; "onProgress": "on-progress"; "pageChange": "pageChange"; }, never, never>;
}

//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoicGRmLXZpZXdlci5jb21wb25lbnQuZC50cyIsInNvdXJjZXMiOlsicGRmLXZpZXdlci5jb21wb25lbnQuZC50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOzs7QUFDQSIsInNvdXJjZXNDb250ZW50IjpbIi8qKlxuICogQ3JlYXRlZCBieSB2YWRpbWRleiBvbiAyMS8wNi8xNi5cbiAqL1xuaW1wb3J0IHsgRWxlbWVudFJlZiwgRXZlbnRFbWl0dGVyLCBPbkNoYW5nZXMsIFNpbXBsZUNoYW5nZXMsIE9uSW5pdCwgT25EZXN0cm95LCBBZnRlclZpZXdDaGVja2VkIH0gZnJvbSAnQGFuZ3VsYXIvY29yZSc7XG5pbXBvcnQgeyBQREZEb2N1bWVudFByb3h5LCBQREZTb3VyY2UsIFBERlByb2dyZXNzRGF0YSB9IGZyb20gJ3BkZmpzLWRpc3QnO1xuZXhwb3J0IGRlY2xhcmUgZW51bSBSZW5kZXJUZXh0TW9kZSB7XG4gICAgRElTQUJMRUQgPSAwLFxuICAgIEVOQUJMRUQgPSAxLFxuICAgIEVOSEFOQ0VEID0gMlxufVxuZXhwb3J0IGRlY2xhcmUgY2xhc3MgUGRmVmlld2VyQ29tcG9uZW50IGltcGxlbWVudHMgT25DaGFuZ2VzLCBPbkluaXQsIE9uRGVzdHJveSwgQWZ0ZXJWaWV3Q2hlY2tlZCB7XG4gICAgcHJpdmF0ZSBlbGVtZW50O1xuICAgIHBkZlZpZXdlckNvbnRhaW5lcjogYW55O1xuICAgIHByaXZhdGUgaXNWaXNpYmxlO1xuICAgIHN0YXRpYyBDU1NfVU5JVFM6IG51bWJlcjtcbiAgICBzdGF0aWMgQk9SREVSX1dJRFRIOiBudW1iZXI7XG4gICAgcHJpdmF0ZSBwZGZNdWx0aVBhZ2VWaWV3ZXI7XG4gICAgcHJpdmF0ZSBwZGZNdWx0aVBhZ2VMaW5rU2VydmljZTtcbiAgICBwcml2YXRlIHBkZk11bHRpUGFnZUZpbmRDb250cm9sbGVyO1xuICAgIHByaXZhdGUgcGRmU2luZ2xlUGFnZVZpZXdlcjtcbiAgICBwcml2YXRlIHBkZlNpbmdsZVBhZ2VMaW5rU2VydmljZTtcbiAgICBwcml2YXRlIHBkZlNpbmdsZVBhZ2VGaW5kQ29udHJvbGxlcjtcbiAgICBwcml2YXRlIF9jTWFwc1VybDtcbiAgICBwcml2YXRlIF9yZW5kZXJUZXh0O1xuICAgIHByaXZhdGUgX3JlbmRlclRleHRNb2RlO1xuICAgIHByaXZhdGUgX3N0aWNrVG9QYWdlO1xuICAgIHByaXZhdGUgX29yaWdpbmFsU2l6ZTtcbiAgICBwcml2YXRlIF9wZGY7XG4gICAgcHJpdmF0ZSBfcGFnZTtcbiAgICBwcml2YXRlIF96b29tO1xuICAgIHByaXZhdGUgX3pvb21TY2FsZTtcbiAgICBwcml2YXRlIF9yb3RhdGlvbjtcbiAgICBwcml2YXRlIF9zaG93QWxsO1xuICAgIHByaXZhdGUgX2NhbkF1dG9SZXNpemU7XG4gICAgcHJpdmF0ZSBfZml0VG9QYWdlO1xuICAgIHByaXZhdGUgX2V4dGVybmFsTGlua1RhcmdldDtcbiAgICBwcml2YXRlIF9zaG93Qm9yZGVycztcbiAgICBwcml2YXRlIGxhc3RMb2FkZWQ7XG4gICAgcHJpdmF0ZSBfbGF0ZXN0U2Nyb2xsZWRQYWdlO1xuICAgIHByaXZhdGUgcmVzaXplVGltZW91dDtcbiAgICBwcml2YXRlIHBhZ2VTY3JvbGxUaW1lb3V0O1xuICAgIHByaXZhdGUgaXNJbml0aWFsaXplZDtcbiAgICBwcml2YXRlIGxvYWRpbmdUYXNrO1xuICAgIGFmdGVyTG9hZENvbXBsZXRlOiBFdmVudEVtaXR0ZXI8UERGRG9jdW1lbnRQcm94eT47XG4gICAgcGFnZVJlbmRlcmVkOiBFdmVudEVtaXR0ZXI8Q3VzdG9tRXZlbnQ8YW55Pj47XG4gICAgdGV4dExheWVyUmVuZGVyZWQ6IEV2ZW50RW1pdHRlcjxDdXN0b21FdmVudDxhbnk+PjtcbiAgICBvbkVycm9yOiBFdmVudEVtaXR0ZXI8YW55PjtcbiAgICBvblByb2dyZXNzOiBFdmVudEVtaXR0ZXI8UERGUHJvZ3Jlc3NEYXRhPjtcbiAgICBwYWdlQ2hhbmdlOiBFdmVudEVtaXR0ZXI8bnVtYmVyPjtcbiAgICBzcmM6IHN0cmluZyB8IFVpbnQ4QXJyYXkgfCBQREZTb3VyY2U7XG4gICAgY01hcHNVcmw6IHN0cmluZztcbiAgICBwYWdlOiBhbnk7XG4gICAgcmVuZGVyVGV4dDogYm9vbGVhbjtcbiAgICByZW5kZXJUZXh0TW9kZTogUmVuZGVyVGV4dE1vZGU7XG4gICAgb3JpZ2luYWxTaXplOiBib29sZWFuO1xuICAgIHNob3dBbGw6IGJvb2xlYW47XG4gICAgc3RpY2tUb1BhZ2U6IGJvb2xlYW47XG4gICAgem9vbTogbnVtYmVyO1xuICAgIHpvb21TY2FsZTogJ3BhZ2UtaGVpZ2h0JyB8ICdwYWdlLWZpdCcgfCAncGFnZS13aWR0aCc7XG4gICAgcm90YXRpb246IG51bWJlcjtcbiAgICBleHRlcm5hbExpbmtUYXJnZXQ6IHN0cmluZztcbiAgICBhdXRvcmVzaXplOiBib29sZWFuO1xuICAgIGZpdFRvUGFnZTogYm9vbGVhbjtcbiAgICBzaG93Qm9yZGVyczogYm9vbGVhbjtcbiAgICBzdGF0aWMgZ2V0TGlua1RhcmdldCh0eXBlOiBzdHJpbmcpOiBhbnk7XG4gICAgc3RhdGljIHNldEV4dGVybmFsTGlua1RhcmdldCh0eXBlOiBzdHJpbmcpOiB2b2lkO1xuICAgIGNvbnN0cnVjdG9yKGVsZW1lbnQ6IEVsZW1lbnRSZWYpO1xuICAgIG5nQWZ0ZXJWaWV3Q2hlY2tlZCgpOiB2b2lkO1xuICAgIG5nT25Jbml0KCk6IHZvaWQ7XG4gICAgbmdPbkRlc3Ryb3koKTogdm9pZDtcbiAgICBvblBhZ2VSZXNpemUoKTogdm9pZDtcbiAgICByZWFkb25seSBwZGZMaW5rU2VydmljZTogYW55O1xuICAgIHJlYWRvbmx5IHBkZlZpZXdlcjogYW55O1xuICAgIHJlYWRvbmx5IHBkZkZpbmRDb250cm9sbGVyOiBhbnk7XG4gICAgbmdPbkNoYW5nZXMoY2hhbmdlczogU2ltcGxlQ2hhbmdlcyk6IHZvaWQ7XG4gICAgdXBkYXRlU2l6ZSgpOiB2b2lkO1xuICAgIGNsZWFyKCk6IHZvaWQ7XG4gICAgcHJpdmF0ZSBzZXR1cE11bHRpUGFnZVZpZXdlcjtcbiAgICBwcml2YXRlIHNldHVwU2luZ2xlUGFnZVZpZXdlcjtcbiAgICBwcml2YXRlIGdldFZhbGlkUGFnZU51bWJlcjtcbiAgICBwcml2YXRlIGdldERvY3VtZW50UGFyYW1zO1xuICAgIHByaXZhdGUgbG9hZFBERjtcbiAgICBwcml2YXRlIHVwZGF0ZTtcbiAgICBwcml2YXRlIHJlbmRlcjtcbiAgICBwcml2YXRlIGdldFNjYWxlO1xuICAgIHByaXZhdGUgZ2V0Q3VycmVudFZpZXdlcjtcbiAgICBwcml2YXRlIHJlc2V0UGRmRG9jdW1lbnQ7XG59XG4iXX0=