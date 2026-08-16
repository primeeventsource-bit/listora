<?php

namespace App\Observability;

/**
 * OpenInference semantic-convention attribute names.
 *
 * Python, TypeScript and Go each ship an `openinference-semantic-conventions`
 * package that exports these as constants. There is no PHP equivalent, so the
 * names are declared here once rather than spelled as string literals at every
 * call site — a typo in an attribute key does not fail loudly, it silently
 * produces a span Arize cannot score.
 *
 * Reference: https://arize.com/docs/ax/instrument/manual-instrumentation
 */
final class OpenInference
{
    // --- core (every span kind) -----------------------------------------
    public const SPAN_KIND = 'openinference.span.kind';

    public const INPUT_VALUE = 'input.value';

    public const OUTPUT_VALUE = 'output.value';

    // --- span kinds ------------------------------------------------------
    public const KIND_LLM = 'LLM';

    public const KIND_CHAIN = 'CHAIN';

    public const KIND_TOOL = 'TOOL';

    public const KIND_RETRIEVER = 'RETRIEVER';

    // --- LLM spans -------------------------------------------------------
    public const LLM_MODEL_NAME = 'llm.model_name';

    public const LLM_PROVIDER = 'llm.provider';

    public const LLM_SYSTEM = 'llm.system';

    public const LLM_TOKEN_COUNT_PROMPT = 'llm.token_count.prompt';

    public const LLM_TOKEN_COUNT_COMPLETION = 'llm.token_count.completion';

    public const LLM_TOKEN_COUNT_TOTAL = 'llm.token_count.total';

    // --- TOOL spans ------------------------------------------------------
    public const TOOL_NAME = 'tool.name';

    public const TOOL_DESCRIPTION = 'tool.description';

    public const TOOL_PARAMETERS = 'tool.parameters';

    public const TOOL_ID = 'tool.id';

    // --- session grouping ------------------------------------------------
    public const SESSION_ID = 'session.id';

    public const USER_ID = 'user.id';

    /** Indexed input-message attribute, e.g. llm.input_messages.0.message.role */
    public static function inputMessageRole(int $i): string
    {
        return "llm.input_messages.{$i}.message.role";
    }

    public static function inputMessageContent(int $i): string
    {
        return "llm.input_messages.{$i}.message.content";
    }

    public static function outputMessageRole(int $i): string
    {
        return "llm.output_messages.{$i}.message.role";
    }

    public static function outputMessageContent(int $i): string
    {
        return "llm.output_messages.{$i}.message.content";
    }

    /** The model's request to call a tool, recorded on the LLM span's output. */
    public static function outputMessageToolCallName(int $i, int $j): string
    {
        return "llm.output_messages.{$i}.message.tool_calls.{$j}.tool_call.function.name";
    }

    public static function outputMessageToolCallArguments(int $i, int $j): string
    {
        return "llm.output_messages.{$i}.message.tool_calls.{$j}.tool_call.function.arguments";
    }
}
