import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from '../../wayfinder';
/**
 * @see \App\Http\Controllers\InternSheetController::monitoring
 * @see app/Http/Controllers/InternSheetController.php:13
 * @route '/project-monitoring'
 */
export const monitoring = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: monitoring.url(options),
    method: 'get',
});

monitoring.definition = {
    methods: ['get', 'head'],
    url: '/project-monitoring',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\InternSheetController::monitoring
 * @see app/Http/Controllers/InternSheetController.php:13
 * @route '/project-monitoring'
 */
monitoring.url = (options?: RouteQueryOptions) => {
    return monitoring.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\InternSheetController::monitoring
 * @see app/Http/Controllers/InternSheetController.php:13
 * @route '/project-monitoring'
 */
monitoring.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitoring.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\InternSheetController::monitoring
 * @see app/Http/Controllers/InternSheetController.php:13
 * @route '/project-monitoring'
 */
monitoring.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: monitoring.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\InternSheetController::monitoring
 * @see app/Http/Controllers/InternSheetController.php:13
 * @route '/project-monitoring'
 */
const monitoringForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: monitoring.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\InternSheetController::monitoring
 * @see app/Http/Controllers/InternSheetController.php:13
 * @route '/project-monitoring'
 */
monitoringForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: monitoring.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\InternSheetController::monitoring
 * @see app/Http/Controllers/InternSheetController.php:13
 * @route '/project-monitoring'
 */
monitoringForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: monitoring.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

monitoring.form = monitoringForm;

const project = {
    monitoring: Object.assign(monitoring, monitoring),
};

export default project;
