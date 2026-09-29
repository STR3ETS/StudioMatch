@php $status = isset($exception) ? $exception->getStatusCode() : 500; @endphp

<x-error-bare :code="$status" :heading="__('errors.5xx.heading')" :text="__('errors.5xx.text')" />
