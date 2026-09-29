@php $status = isset($exception) ? $exception->getStatusCode() : 400; @endphp

<x-error-page :code="$status" :heading="__('errors.4xx.heading')" :text="__('errors.4xx.text')" />
