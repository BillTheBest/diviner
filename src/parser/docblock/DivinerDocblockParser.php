<?php

/*
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Parse a docblock comment from source code into raw text documentation and
 * metadata (like "@author" and "@return").
 */
class DivinerDocblockParser {

  public function parse($docblock) {

    // Strip off comments.
    $docblock = trim($docblock);
    $docblock = preg_replace('@^/\*\*@', '', $docblock);
    $docblock = preg_replace('@\*/$@', '', $docblock);
    $docblock = preg_replace('@^\s*\*@m', '', $docblock);

    // Normalize multi-line @specials.
    $lines = explode("\n", $docblock);
    $last = false;
    foreach ($lines as $k => $line) {
      if (preg_match('/^\s*@\w/i', $line)) {
        $last = $k;
        continue;
      } else if (preg_match('/^\s*$/', $line)) {
        $last = false;
      } else if ($last) {
        $lines[$last] = rtrim($lines[$last]).' '.trim($line);
        unset($lines[$k]);
      }
    }
    $docblock = implode("\n", $lines);

    $special = array();

    // Parse @specials.
    $matches = null;
    $have_specials = preg_match_all(
      '/^\s*@(\w+)\s*([^\n]*)/m',
      $docblock,
      $matches,
      PREG_SET_ORDER);
    if ($have_specials) {
      $docblock = preg_replace('/^\s*@(\w+)\s*([^\n]*)/m', '', $docblock);
      foreach ($matches as $match) {
        list($_, $type, $data) = $match;
        $data = trim($data);
        if (isset($special[$type])) {
          $special[$type] = $special[$type]."\n".$data;
        } else {
          $special[$type] = $data;
        }
      }
    }

    $docblock = str_replace("\t", '  ', $docblock);

    // Smush the whole docblock to the left edge.
    $min_indent = 80;
    $indent = 0;
    foreach (array_filter(explode("\n", $docblock)) as $line) {
      for ($ii = 0; $ii < strlen($line); $ii++) {
        if ($line[$ii] != ' ') {
          break;
        }
        $indent++;
      }
      $min_indent = min($indent, $min_indent);
    }

    $docblock = preg_replace(
      '/^'.str_repeat(' ', $min_indent).'/m',
      '',
      $docblock);
    $docblock = rtrim($docblock);
    // Trim any empty lines off the front, but leave the indent level if there
    // is one.
    $docblock = preg_replace('/^\s*\n/', '', $docblock);

    return array($docblock, $special);
  }
}
